-- Drop all tables if they exist.
DROP TABLE IF EXISTS countries;
DROP TABLE IF EXISTS regions;
DROP TABLE IF EXISTS places;
DROP TABLE IF EXISTS transport_routes;
DROP TABLE IF EXISTS transport_route_master;
DROP TABLE IF EXISTS transport_stops;
DROP TABLE IF EXISTS transport_location;
DROP TABLE IF EXISTS transport_validation;
DROP TABLE IF EXISTS transport_validation_prev;
DROP TABLE IF EXISTS incorrect_routes;
DROP TABLE IF EXISTS statistics_by_region;
DROP TABLE IF EXISTS statistics_summary;

-- Create tables
CREATE TABLE countries (
	id BIGINT NOT NULL,
	iso3166_1 varchar(2) NOT NULL,
	name text NOT NULL,
	geom geometry NOT NULL,
	PRIMARY KEY(id));

CREATE TABLE regions (
	id BIGINT NOT NULL,
	iso3166_1 varchar(2) NOT NULL,
	iso3166_2 varchar(6),
	federal_district text NOT NULL,
	name text NOT NULL,
	geom geometry NOT NULL,
	PRIMARY KEY(id));

CREATE TABLE places (
	id BIGINT NOT NULL,
	region_id BIGINT NOT NULL,
	type text NOT NULL,
	name text NOT NULL,
	geom geometry NOT NULL,
	PRIMARY KEY(id));

CREATE TABLE transport_routes (
	id BIGINT NOT NULL,
	tstamp timestamp NOT NULL,
	tags HSTORE NOT NULL,
	geom GEOMETRY,
	length DOUBLE PRECISION,
	version SMALLINT,
	is_valid BOOLEAN,
	PRIMARY KEY(id));

CREATE TABLE incorrect_routes (
	id BIGINT NOT NULL,
	fault_pos INTEGER[],
	PRIMARY KEY(id));

CREATE TABLE transport_route_master (
	id BIGINT NOT NULL,
	tstamp timestamp NOT NULL,
	tags HSTORE NOT NULL,
	routes BIGINT[] NOT NULL,
	PRIMARY KEY(id));

CREATE TABLE transport_stops (
	id BIGINT NOT NULL,
	tstamp timestamp NOT NULL,
	tags HSTORE NOT NULL,
	geom GEOMETRY NOT NULL,
	geom_center GEOMETRY,
	PRIMARY KEY(id));

CREATE TABLE transport_location (
	region_id BIGINT NOT NULL,
	place_id BIGINT,
	route_id BIGINT NOT NULL);

CREATE TABLE transport_validation(
	region_id BIGINT NOT NULL,
	routes BIGINT,
	no_ref BIGINT,
	no_name BIGINT,
	no_from_to BIGINT,
	wrong_geom BIGINT,
	PRIMARY KEY(region_id));

CREATE TABLE transport_validation_prev(
	region_id BIGINT NOT NULL,
	routes BIGINT,
	no_ref BIGINT,
	no_name BIGINT,
	no_from_to BIGINT,
	wrong_geom BIGINT,
	PRIMARY KEY(region_id));

CREATE TABLE statistics_by_region(
	tstamp timestamp NOT NULL,
	region_id BIGINT NOT NULL,
	route_bus BIGINT,
	route_trolleybus BIGINT,
	route_share_taxi BIGINT,
	route_tram BIGINT,
	route_train BIGINT,
	route_master_bus BIGINT,
	route_master_trolleybus BIGINT,
	route_master_share_taxi BIGINT,
	route_master_tram BIGINT,
	route_master_train BIGINT,
	stop_position BIGINT,
	platform BIGINT,
	station BIGINT,
	PRIMARY KEY(tstamp, region_id));

CREATE TABLE statistics_summary(
	tstamp timestamp NOT NULL,
	route_bus BIGINT,
	route_trolleybus BIGINT,
	route_share_taxi BIGINT,
	route_tram BIGINT,
	route_train BIGINT,
	route_master_bus BIGINT,
	route_master_trolleybus BIGINT,
	route_master_share_taxi BIGINT,
	route_master_tram BIGINT,
	route_master_train BIGINT,
	stop_position BIGINT,
	platform BIGINT,
	station BIGINT,
	PRIMARY KEY(tstamp));

-- Add indexes to tables.
CREATE INDEX idx_stops_geom ON transport_stops USING gist (geom);
CREATE INDEX idx_stops_geom_center ON transport_stops USING gist (geom_center);

-- Create functions
CREATE OR REPLACE FUNCTION RouteIsValid(route_id BIGINT, save_to_table BOOLEAN)
RETURNS BOOLEAN as $$
DECLARE
	cur RECORD;
	tmp BIGINT[];
	tmp_pos INTEGER;
	i INTEGER := 0;
	ways_pos INTEGER[];
BEGIN
	FOR cur IN
		(SELECT
			relation_members.sequence_id as way_pos,
			ARRAY[ways.nodes[1], ways.nodes[array_upper(ways.nodes,1)]] as extreme_nodes
		FROM relations, relation_members, ways
		WHERE
			relations.id=relation_members.relation_id and
			relation_members.member_role in('','forward','backward') and
			relation_members.member_id=ways.id and
			relations.id=route_id
		ORDER BY way_pos)
	LOOP
		IF i=0 THEN
			i:=1;
		ELSIF not(tmp && cur.extreme_nodes) THEN
			i:=i+1;
			ways_pos:=array_append(ways_pos, tmp_pos);
		END IF;
		tmp:=cur.extreme_nodes;
		tmp_pos:=cur.way_pos;
	END LOOP;

	IF save_to_table=true and i>1 THEN
		INSERT INTO incorrect_routes (id, fault_pos)
		SELECT route_id, ways_pos;
	END IF;

	IF i=1 THEN
		RETURN true;
	ELSE
		RETURN false;
	END IF;
END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION CheckRouteVer(route_id BIGINT)
RETURNS SMALLINT as $$
DECLARE
	i integer := 0;
BEGIN
	i:=(
	SELECT
		count(member_role) as count
	FROM relation_members
	WHERE
		relation_members.member_role in ('forward','backward','forward:stop','backward:stop') and
		relation_members.relation_id=route_id);

	IF i>0 THEN
		RETURN 1;
	ELSE
		RETURN 2;
	END IF;
END;
$$ LANGUAGE plpgsql;
