-- ## Получение стран ##
BEGIN;
	TRUNCATE TABLE countries;

	-- Внешняя геометрия

	CREATE TEMP TABLE IF NOT EXISTS
	countries_outer (
		id BIGINT NOT NULL,
		iso3166_1 varchar(2),
		name text NOT NULL,
		geom geometry,
	PRIMARY KEY(id)
	);

	TRUNCATE TABLE countries_outer;

	INSERT INTO countries_outer (id, iso3166_1, name, geom)
	SELECT
		id,
		iso3166_1,
		name,
		ST_BuildArea(ST_Union(geom)) as geom
	FROM
		(SELECT
			relations.id,
			relations.tags->'ISO3166-1' as iso3166_1,
			relations.tags->'name' as name,
			relation_members.sequence_id as way_pos,
			ST_MakeLine(nodes.geom) as geom
		FROM relations, relation_members, ways, nodes, unnest(ways.nodes) WITH ORDINALITY AS t_nodes(node_id,node_pos)
		WHERE
			relations.tags->'ISO3166-1' in ('BY','RU','UA') and
			relations.tags->'admin_level'='2' and
			relations.tags->'boundary'='administrative' and
			relations.id=relation_members.relation_id and
			relation_members.member_id=ways.id and
			nodes.id = t_nodes.node_id and
			relation_members.member_role='outer'
		GROUP BY relations.id, iso3166_1, name, way_pos) as t_lines
	GROUP BY id, iso3166_1, name
	ORDER BY iso3166_1;

	-- Внутренняя геометрия

	CREATE TEMP TABLE IF NOT EXISTS
	countries_inner (
		id BIGINT NOT NULL,
		iso3166_1 varchar(2),
		name text NOT NULL,
		geom geometry,
	PRIMARY KEY(id)
	);

	TRUNCATE TABLE countries_inner;

	INSERT INTO countries_inner (id, iso3166_1, name, geom)
	SELECT
		id,
		iso3166_1,
		name,
		ST_BuildArea(ST_Union(geom)) as geom
	FROM
		(SELECT
			relations.id,
			relations.tags->'ISO3166-1' as iso3166_1,
			relations.tags->'name' as name,
			relation_members.sequence_id as way_pos,
			ST_MakeLine(nodes.geom) as geom
		FROM relations, relation_members, ways, nodes, unnest(ways.nodes) WITH ORDINALITY AS t_nodes(node_id,node_pos)
		WHERE
			relations.tags->'ISO3166-1' in ('BY','RU','UA') and
			relations.tags->'admin_level'='2' and
			relations.tags->'boundary'='administrative' and
			relations.id=relation_members.relation_id and
			relation_members.member_id=ways.id and
			nodes.id = t_nodes.node_id and
			relation_members.member_role='inner'
		GROUP BY relations.id, iso3166_1, name, way_pos) as t_lines
	GROUP BY id, iso3166_1, name
	ORDER BY iso3166_1;

	-- Заносим страны с внутренней геометрией

	INSERT INTO countries (id, iso3166_1, name, geom)
	SELECT
		countries_outer.id,
		countries_outer.iso3166_1,
		countries_outer.name,
		ST_BuildArea(ST_Collect(countries_inner.geom, countries_outer.geom)) as geom
	FROM countries_inner, countries_outer
	WHERE
		countries_inner.id=countries_outer.id and
		countries_inner.geom <> '' and
		countries_outer.geom <> '';

	-- Заносим остатки из countries_outer

	INSERT INTO countries (id, iso3166_1, name, geom)
	SELECT
		id,
		iso3166_1,
		name,
		geom
	FROM countries_outer
	WHERE
		id not in (SELECT id FROM countries_inner) and
		geom <> '';
END;

-- ## Получение регионов ##
BEGIN;
	TRUNCATE TABLE regions;

	-- Внешняя геометрия

	CREATE TEMP TABLE IF NOT EXISTS
	regions_outer (
		id BIGINT NOT NULL,
		iso3166_1 varchar(2) NOT NULL,
		iso3166_2 varchar(6),
		federal_district text NOT NULL,
		name text NOT NULL,
		geom geometry
	);

	TRUNCATE TABLE regions_outer;

	INSERT INTO regions_outer (id, iso3166_1, iso3166_2, federal_district, name, geom)
	SELECT
		regions.id,
		regions.iso3166_1,
		regions.iso3166_2,
		relations.tags->'name' as federal_district,
		regions.name,
		ST_BuildArea(ST_Union(regions.geom)) as geom
	FROM relations, relation_members,
		(SELECT
			relations.id,
			countries.iso3166_1,
			relations.tags->'ISO3166-2' as iso3166_2,
			relations.tags->'name' as name,
			relation_members.sequence_id as way_pos,
			ST_MakeLine(nodes.geom) as geom
		FROM countries, relations, relation_members, ways, nodes, unnest(ways.nodes) WITH ORDINALITY AS t_nodes(node_id,node_pos)
		WHERE
			relations.tags->'admin_level'='4' and
			relations.tags->'boundary'='administrative' and
			relations.id=relation_members.relation_id and
			relation_members.member_id=ways.id and
			nodes.id = t_nodes.node_id and
			relation_members.member_role='outer' and
			(relations.tags->'is_in:country_code'=countries.iso3166_1 or
			relations.tags->'addr:country'=countries.iso3166_1)
		GROUP BY relations.id, countries.iso3166_1, iso3166_2, name, way_pos) as regions
	WHERE
		relations.id=relation_members.relation_id and
		relation_members.member_role='subarea' and
		regions.id=relation_members.member_id
	GROUP BY regions.id, regions.iso3166_1, regions.iso3166_2, federal_district, regions.name
	ORDER BY regions.iso3166_2;

	-- Внутренняя геометрия

	CREATE TEMP TABLE IF NOT EXISTS
	regions_inner (
		id BIGINT NOT NULL,
		iso3166_1 varchar(2) NOT NULL,
		iso3166_2 varchar(6),
		federal_district text NOT NULL,
		name text NOT NULL,
		geom geometry
	);

	TRUNCATE TABLE regions_inner;

	INSERT INTO regions_inner (id, iso3166_1, iso3166_2, federal_district, name, geom)
	SELECT
		regions.id,
		regions.iso3166_1,
		regions.iso3166_2,
		relations.tags->'name' as federal_district,
		regions.name,
		ST_BuildArea(ST_Union(regions.geom)) as geom
	FROM relations, relation_members,
		(SELECT
			relations.id,
			countries.iso3166_1,
			relations.tags->'ISO3166-2' as iso3166_2,
			relations.tags->'name' as name,
			relation_members.sequence_id as way_pos,
			ST_MakeLine(nodes.geom) as geom
		FROM countries, relations, relation_members, ways, nodes, unnest(ways.nodes) WITH ORDINALITY AS t_nodes(node_id,node_pos)
		WHERE
			relations.tags->'admin_level'='4' and
			relations.tags->'boundary'='administrative' and
			relations.id=relation_members.relation_id and
			relation_members.member_id=ways.id and
			nodes.id = t_nodes.node_id and
			relation_members.member_role='inner' and
			(relations.tags->'is_in:country_code'=countries.iso3166_1 or
			relations.tags->'addr:country'=countries.iso3166_1)
		GROUP BY relations.id, countries.iso3166_1, iso3166_2, name, way_pos) as regions
	WHERE
		relations.id=relation_members.relation_id and
		relation_members.member_role='subarea' and
		regions.id=relation_members.member_id
	GROUP BY regions.id, regions.iso3166_1, regions.iso3166_2, federal_district, regions.name
	ORDER BY regions.iso3166_2;

	-- Заносим регионы с внутренней геометрией

	INSERT INTO regions (id, iso3166_1, iso3166_2, federal_district, name, geom)
	SELECT regions_outer.id, regions_outer.iso3166_1, regions_outer.iso3166_2, regions_outer.federal_district, regions_outer.name, ST_BuildArea(ST_Collect(regions_inner.geom, regions_outer.geom)) as geom
	FROM regions_inner, regions_outer
	WHERE
		regions_inner.id=regions_outer.id and
		regions_inner.geom <> '' and
		regions_outer.geom <> '';

	-- Заносим остатки из regions_outer

	INSERT INTO regions (id, iso3166_1, iso3166_2, federal_district, name, geom)
	SELECT regions_outer.id, regions_outer.iso3166_1, regions_outer.iso3166_2, regions_outer.federal_district, regions_outer.name, regions_outer.geom
	FROM regions_outer
	WHERE
		regions_outer.id not in (SELECT id FROM regions_inner) and
		regions_outer.geom <> '';
END;

-- ## Получение населенных пунктов ##
BEGIN;
	TRUNCATE TABLE places;

	-- Relations

	INSERT INTO places (id,	region_id, type, name, geom)
	Select places.id as id, regions.id as region_id, places.type, places.name, places.geom
	from regions,
		(SELECT
			places_l.id,
			places_l.type,
			places_l.name,
			ST_BuildArea(ST_Union(places_l.geom)) as geom
		FROM
			(SELECT
				places_n.id,
				places_n.type,
				places_n.name,
				places_n.way_pos,
				ST_MakeLine(places_n.geom) as geom
			FROM
				(SELECT
					relations.id,
					relations.tags->'place' as type,
					relations.tags->'name' as name,
					relation_members.sequence_id as way_pos,
					t_nodes.node_pos,
					nodes.geom as geom
				FROM relations, relation_members, ways, nodes, unnest(ways.nodes) WITH ORDINALITY AS t_nodes(node_id,node_pos)
				WHERE
					(relations.tags->'place'='city' or
					relations.tags->'place'='town' or
					relations.tags->'place'='village') and
					relations.tags->'name'<>'' and
					nodes.id = t_nodes.node_id and
					relation_members.relation_id=relations.id and
					relation_members.member_role='outer' and
					ways.id=relation_members.member_id
				GROUP BY relations.id, type, name, way_pos, node_pos, geom
				ORDER BY relations.id, type, name, way_pos, node_pos) as places_n
			GROUP BY places_n.id, places_n.type, places_n.name, places_n.way_pos
			ORDER BY places_n.id, places_n.type, places_n.name, places_n.way_pos) as places_l
		GROUP BY places_l.id, places_l.type, places_l.name
		ORDER BY places_l.type, places_l.name) as places

	WHERE ST_Contains(regions.geom, places.geom)
	ORDER BY places.type, places.name;

	-- Ways

	INSERT INTO places (id,	region_id, type, name, geom)
	Select places.id as id, regions.id as region_id, places.type, places.name, places.geom
	from regions,
		(SELECT
			places.id,
			places.type,
			places.name,
			ST_MakePolygon(ST_MakeLine(places.geom)) as geom
		FROM
			(SELECT
				ways.id,
				ways.tags->'place' as type,
				ways.tags->'name' as name,
				t_nodes.node_pos,
				nodes.geom as geom
			FROM ways, nodes, unnest(ways.nodes) WITH ORDINALITY AS t_nodes(node_id,node_pos)
			WHERE
				(ways.tags->'place'='city' or
				ways.tags->'place'='town' or
				ways.tags->'place'='village') and
				nodes.id = t_nodes.node_id and
				ways.tags->'name'<>''
			GROUP BY ways.id, type, name, t_nodes.node_pos, geom
			ORDER BY ways.id, type, name, node_pos) as places
		GROUP BY places.id, places.type, places.name) as places
	WHERE ST_Contains(regions.geom, places.geom)
	ORDER BY places.type, places.name;
END;
