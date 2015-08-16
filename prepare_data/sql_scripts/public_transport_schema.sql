-- Drop all tables if they exist.
DROP TABLE IF EXISTS regions;
DROP TABLE IF EXISTS places;
DROP TABLE IF EXISTS transport_routes;
DROP TABLE IF EXISTS transport_route_master;
DROP TABLE IF EXISTS transport_stops;
DROP TABLE IF EXISTS transport_location;
DROP TABLE IF EXISTS transport_validation;
DROP TABLE IF EXISTS transport_validation_prev;
DROP TABLE IF EXISTS statistics_by_region;
DROP TABLE IF EXISTS statistics_summary;

-- Create tables
CREATE TABLE regions (
	id BIGINT NOT NULL,
	iso3166 text,
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
	PRIMARY KEY(region_id));

CREATE TABLE transport_validation_prev(
	region_id BIGINT NOT NULL,
	routes BIGINT,
	no_ref BIGINT,
	no_name BIGINT,
	no_from_to BIGINT,
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
