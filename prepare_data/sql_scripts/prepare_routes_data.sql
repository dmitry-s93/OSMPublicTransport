BEGIN;
	TRUNCATE TABLE transport_routes;

	INSERT INTO transport_routes (id,tstamp,tags,geom,length)
	SELECT
		routes.route_id as id,
		routes.tstamp,
		relations.tags,
		routes.geom,
		ST_Length(routes.geom, true) as length
	FROM
		relations,
		(SELECT
			route_id,
			MAX(ways.tstamp) as tstamp,
			ST_LineMerge(ST_Union(ways.geom)) as geom
		FROM
			(SELECT
				route_id,
				way_pos,
				MAX(nodes.tstamp) as tstamp,
				ST_makeLine(nodes.geom) as geom
			FROM
				(SELECT 
					relations.id as route_id,
					t_nodes.node_pos as node_pos,
					relation_members.sequence_id as way_pos,
					relations.tstamp,
					nodes.geom as geom
				FROM relations, relation_members, ways, nodes, unnest(ways.nodes) WITH ORDINALITY AS t_nodes(node_id,node_pos)
				WHERE
					relations.tags->'type'='route' and
					relations.tags->'route' in ('bus','trolleybus','share_taxi','tram','train') and
					relations.id=relation_members.relation_id and
					relation_members.member_role in('','forward','backward') and
					relation_members.member_id=ways.id and
					nodes.id = t_nodes.node_id
				ORDER BY way_pos, node_pos) as nodes
			GROUP BY route_id, way_pos) as ways
		GROUP BY route_id) as routes
	WHERE routes.route_id=relations.id;
END;

------------------------------------------------------------------------

BEGIN;
	TRUNCATE TABLE transport_route_master;

	INSERT INTO transport_route_master (id,tstamp,tags,routes)
	SELECT
		relations.id,
		relations.tstamp,
		relations.tags,
		array_agg(relation_members.member_id) as routes
	FROM relations, relation_members
	WHERE
		relations.tags->'type'='route_master' and
		(relations.tags->'route_master' in ('bus','trolleybus','share_taxi','tram','train') or
		relations.tags->'route' in ('bus','trolleybus','share_taxi','tram','train')) and
		relations.id=relation_members.relation_id
	GROUP BY
		relations.id,
		relations.tags;
END;

------------------------------------------------------------------------

BEGIN;
	TRUNCATE TABLE transport_stops;

	-- Точечные
	INSERT INTO transport_stops (id,tstamp,tags,geom)
	SELECT
		id,
		tstamp,
		tags,
		geom
	FROM nodes
	WHERE
		tags->'public_transport' in ('stop_position', 'platform','station') or
		tags->'highway'='bus_stop' or tags->'amenity'='bus_station' or
		tags->'railway' in ('stop', 'tram_stop','halt','station');

	-- Линии и полигоны
	INSERT INTO transport_stops (id,tstamp,tags,geom)
	SELECT
		id,
		tstamp,
		tags,
		(case when ST_IsRing(ST_MakeLine(nodes.geom)) is true then ST_BuildArea(ST_MakeLine(nodes.geom))
			else (ST_MakeLine(nodes.geom)) end) as geom
	FROM
		(SELECT
			ways.id,
			ways.tstamp,
			ways.tags,
			t_nodes.node_pos,
			nodes.geom as geom
		FROM ways, unnest(ways.nodes) WITH ORDINALITY AS t_nodes(node_id,node_pos), nodes
		WHERE
			(ways.tags->'public_transport' in ('platform','station') or
			ways.tags->'amenity'='bus_station' or
			ways.tags->'railway'='station') and
			nodes.id=t_nodes.node_id
		ORDER BY
			id,
			t_nodes.node_pos) as nodes
	GROUP BY
		id,
		tstamp,
		tags;
END;

------------------------------------------------------------------------

--Маршруты по населенным пунктам
BEGIN;
TRUNCATE TABLE transport_location;

INSERT INTO transport_location (region_id, place_id, route_id)
SELECT
	regions.id as region_id,
	places.id as place_id,
	transport_routes.id as route_id
FROM
	places, regions, transport_routes
WHERE
	regions.id=places.region_id and
	transport_routes.tags->'route' in ('bus','trolleybus','share_taxi','tram') and
	(ST_Contains(places.geom,transport_routes.geom) or
	ST_Crosses(places.geom,transport_routes.geom));
END;

--Маршруты, не попавшие в населенные пункты
BEGIN;
INSERT INTO transport_location (region_id, place_id, route_id)
SELECT
	regions.id as region_id,
	NULL as place_id,
	transport_routes.id as route_id
FROM
	regions, transport_routes
WHERE
	transport_routes.id not in(SELECT DISTINCT route_id from transport_location) and
	transport_routes.tags->'route' in ('bus','trolleybus','share_taxi','tram') and
	(ST_Contains(regions.geom,transport_routes.geom) or
	ST_Crosses(regions.geom,transport_routes.geom));
END;

--Маршруты поездов в населенных пунктах (определяем по местам остановок)
BEGIN;
INSERT INTO transport_location (region_id, place_id, route_id)
SELECT DISTINCT
	regions.id as region_id,
	places.id as place_id,
	routes.id as route_id
FROM places, regions,
	(SELECT DISTINCT
		transport_routes.id,
		transport_stops.id as node_id,
		transport_stops.geom
	FROM transport_routes, relation_members, transport_stops
	WHERE
		transport_routes.tags->'type'='route' and
		transport_routes.tags->'route'='train' and
		transport_routes.id=relation_members.relation_id and
		relation_members.member_role='stop' and
		relation_members.member_id=transport_stops.id) as routes
WHERE
	regions.id=places.region_id and
	(ST_Contains(places.geom,routes.geom) or
	ST_Crosses(places.geom,routes.geom));
END;

------------------------------------------------------------------------

BEGIN;
	TRUNCATE TABLE transport_validation;

	INSERT INTO
	transport_validation(
		region_id,
		routes,
		no_ref,
		no_name,
		no_from_to)
	SELECT
		routes.region_id,
		count(routes.route_id) as routes,
		count(case when routes.ref is null then true else null end) as no_ref,
		count(case when routes.name is null then true else null end) as no_name,
		count(case when (routes.from is null or routes.to is null) then true else null end) as no_from_to
	FROM
		regions,
		(SELECT DISTINCT
			transport_location.region_id,
			transport_routes.id as route_id,
			transport_routes.tags->'route' as type,
			transport_routes.tags->'ref' as ref,
			transport_routes.tags->'name' as name,
			transport_routes.tags->'from' as from,
			transport_routes.tags->'to' as to
		FROM
			transport_routes,
			transport_location
		WHERE
			transport_routes.id in (transport_location.route_id)) as routes
	WHERE
		regions.id=routes.region_id

	GROUP BY
		routes.region_id;
END;

------------------------------------------------------------------------

BEGIN;
	INSERT INTO statistics_by_region(
		region_id,tstamp,
		route_bus,route_trolleybus,route_share_taxi,route_tram,route_train,
		route_master_bus,route_master_trolleybus,route_master_share_taxi,route_master_tram,route_master_train,
		stop_position,platform,station)
	SELECT
		routes.region_id,
		data_tstamp.tstamp,
		routes.route_bus,
		routes.route_trolleybus,
		routes.route_share_taxi,
		routes.route_tram,
		routes.route_train,
		route_master.route_master_bus,
		route_master.route_master_trolleybus,
		route_master.route_master_share_taxi,
		route_master.route_master_tram,
		route_master.route_master_train,
		stops.stop_position,
		stops.platform,
		stops.station
	FROM
		(SELECT DISTINCT
			regions.id as region_id,
			count(case when routes.tags->'route'='bus' then true else null end) as route_bus,
			count(case when routes.tags->'route'='trolleybus' then true else null end) as route_trolleybus,
			count(case when routes.tags->'route'='share_taxi' then true else null end) as route_share_taxi,
			count(case when routes.tags->'route'='tram' then true else null end) as route_tram,
			count(case when routes.tags->'route'='train' then true else null end) as route_train
		FROM 
			regions LEFT JOIN
			(SELECT DISTINCT
				transport_routes.id,
				transport_routes.tags,
				transport_location.region_id
			FROM
				transport_routes,
				transport_location
			WHERE
				transport_location.route_id=transport_routes.id) as routes
			ON regions.id=routes.region_id
		GROUP BY regions.id) as routes,

		(SELECT DISTINCT
			regions.id as region_id,
			count(case when route_master.tags->'route_master'='bus' then true else null end) as route_master_bus,
			count(case when route_master.tags->'route_master'='trolleybus' then true else null end) as route_master_trolleybus,
			count(case when route_master.tags->'route_master'='share_taxi' then true else null end) as route_master_share_taxi,
			count(case when route_master.tags->'route_master'='tram' then true else null end) as route_master_tram,
			count(case when route_master.tags->'route_master'='train' then true else null end) as route_master_train
		FROM 
			regions LEFT JOIN
			(SELECT DISTINCT
				transport_route_master.id,
				transport_route_master.tags,
				transport_location.region_id
			FROM
				transport_routes,
				transport_location,
				(SELECT DISTINCT
					id,
					tags,
					unnest(routes) as routes
				FROM transport_route_master) as transport_route_master
			WHERE
				transport_location.route_id=transport_routes.id and
				transport_routes.id = transport_route_master.routes) as route_master
			ON regions.id=route_master.region_id
		GROUP BY regions.id) as route_master,

		(SELECT DISTINCT
			regions.id as region_id,	
			count(case when (stops.tags->'public_transport'='stop_position' or stops.tags->'railway'='tram_stop' or stops.tags->'railway'='stop') then true else null end) as stop_position,
			count(case when (stops.tags->'public_transport'='platform' or stops.tags->'highway'='bus_stop') then true else null end) as platform,
			count(case when (stops.tags->'public_transport'='station' or stops.tags->'railway'='station' or stops.tags->'railway'='halt') then true else null end) as station
		FROM 
			regions LEFT JOIN
			(SELECT DISTINCT
				transport_stops.id,
				transport_stops.tags,
				regions.id as region_id
			FROM
				transport_stops,
				regions
			WHERE
				ST_Contains(regions.geom,transport_stops.geom)) as stops
			ON regions.id=stops.region_id
		GROUP BY regions.id) as stops,
		(SELECT MAX(tstamp) as tstamp
		FROM
			(SELECT MAX(tstamp) as tstamp
			FROM transport_routes
			UNION
			SELECT MAX(tstamp) as tstamp
			FROM transport_route_master
			UNION
			SELECT MAX(tstamp) as tstamp
			FROM transport_stops) as tstamp
		) as data_tstamp
	WHERE
		routes.region_id=stops.region_id and
		routes.region_id=route_master.region_id;
END;

------------------------------------------------------------------------

BEGIN;
	INSERT INTO statistics_summary(
		tstamp,
		route_bus,route_trolleybus,route_share_taxi,route_tram,route_train,
		route_master_bus,route_master_trolleybus,route_master_share_taxi,route_master_tram,route_master_train,
		stop_position,platform,station)
	SELECT
		data_tstamp.tstamp,
		routes.route_bus,
		routes.route_trolleybus,
		routes.route_share_taxi,
		routes.route_tram,
		routes.route_train,
		route_master.route_master_bus,
		route_master.route_master_trolleybus,
		route_master.route_master_share_taxi,
		route_master.route_master_tram,
		route_master.route_master_train,
		stops.stop_position,
		stops.platform,
		stops.station
	FROM
		(SELECT DISTINCT
			now() as tstamp,
			count(case when transport_routes.tags->'route'='bus' then true else null end) as route_bus,
			count(case when transport_routes.tags->'route'='trolleybus' then true else null end) as route_trolleybus,
			count(case when transport_routes.tags->'route'='share_taxi' then true else null end) as route_share_taxi,
			count(case when transport_routes.tags->'route'='tram' then true else null end) as route_tram,
			count(case when transport_routes.tags->'route'='train' then true else null end) as route_train
		FROM 
			transport_routes) as routes,
		(SELECT DISTINCT
			count(case when transport_route_master.tags->'route_master'='bus' then true else null end) as route_master_bus,
			count(case when transport_route_master.tags->'route_master'='trolleybus' then true else null end) as route_master_trolleybus,
			count(case when transport_route_master.tags->'route_master'='share_taxi' then true else null end) as route_master_share_taxi,
			count(case when transport_route_master.tags->'route_master'='tram' then true else null end) as route_master_tram,
			count(case when transport_route_master.tags->'route_master'='train' then true else null end) as route_master_train
		FROM 
			transport_route_master) as route_master,
 		(SELECT DISTINCT	
			count(case when (transport_stops.tags->'public_transport'='stop_position' or transport_stops.tags->'railway'='tram_stop' or transport_stops.tags->'railway'='stop') then true else null end) as stop_position,
			count(case when (transport_stops.tags->'public_transport'='platform' or transport_stops.tags->'highway'='bus_stop') then true else null end) as platform,
			count(case when (transport_stops.tags->'public_transport'='station' or transport_stops.tags->'railway'='station' or transport_stops.tags->'railway'='halt') then true else null end) as station
		FROM 
			transport_stops) as stops,
		(SELECT MAX(tstamp) as tstamp
		FROM
			(SELECT MAX(tstamp) as tstamp
			FROM transport_routes
			UNION
			SELECT MAX(tstamp) as tstamp
			FROM transport_route_master
			UNION
			SELECT MAX(tstamp) as tstamp
			FROM transport_stops) as tstamp
		) as data_tstamp;
END;
