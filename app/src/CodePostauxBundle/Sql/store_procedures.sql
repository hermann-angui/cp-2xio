DELIMITER $

DROP FUNCTION IF EXISTS zip_deg2rad$
DROP FUNCTION IF EXISTS zip_gcd$
DROP FUNCTION IF EXISTS zip_distance$
DROP PROCEDURE IF EXISTS zip_radius$

CREATE FUNCTION zip_deg2rad(DEGREES DOUBLE) RETURNS DOUBLE
    BEGIN
        RETURN DEGREES / (180 / PI()+0.000000000000000);
    END$

CREATE FUNCTION zip_gcd(type ENUM('M', 'N', 'K'), src_lat DOUBLE, src_long DOUBLE, dst_lat DOUBLE, dst_long DOUBLE) RETURNS DOUBLE
    BEGIN
        DECLARE temp DOUBLE;

        DECLARE STATUTE_MILES DECIMAL(5,1);
        DECLARE NAUTICAL_MILES DECIMAL(9,5);
        DECLARE KILOMETERS DECIMAL(5,1);

        SET STATUTE_MILES = 3963.0;
        SET NAUTICAL_MILES = 3437.74677;
        SET KILOMETERS = 6378.7;

        SET src_lat = zip_deg2rad(src_lat);
        SET src_long = zip_deg2rad(src_long);
        SET dst_lat = zip_deg2rad(dst_lat);
        SET dst_long = zip_deg2rad(dst_long);

        SET temp = ACOS(SIN(src_lat) * SIN(dst_lat) + COS(src_lat) * COS(dst_lat) * COS(dst_long - src_long));

        IF type = "M" THEN
            SET temp = STATUTE_MILES * temp;
        END IF;

        IF type = "N" THEN
            SET temp = NAUTICAL_MILES * temp;
        END IF;

        IF type = "K" THEN
            SET temp = KILOMETERS * temp;
        END IF;

        RETURN temp;
    END$

CREATE FUNCTION zip_distance(type ENUM('M', 'N', 'K'), zip_start VARCHAR(5), zip_finish VARCHAR(5)) RETURNS DOUBLE
    BEGIN
        DECLARE distance DOUBLE;

        DECLARE start_lat DOUBLE;
        DECLARE start_long DOUBLE;
        DECLARE finish_lat DOUBLE;
        DECLARE finish_long DOUBLE;

        SELECT latitude, longitude INTO start_lat, start_long FROM communes WHERE codePostal = zip_start;
        SELECT latitude, longitude INTO finish_lat, finish_long FROM communes WHERE codePostal = zip_finish;

        SELECT zip_gcd(type, start_lat, start_long, finish_lat, finish_long) INTO distance;

        RETURN distance;
    END$

CREATE PROCEDURE zip_radius(IN type ENUM('M', 'N', 'K'), IN zip_start VARCHAR(5), IN radius INT, prec INT)
    BEGIN
        DECLARE src_lat DOUBLE;
        DECLARE src_long DOUBLE;

        SELECT latitude, longitude INTO src_lat, src_long FROM communes WHERE codePostal = zip_start LIMIT 1;

        SELECT codePostal, nom, latitude, longitude, ROUND(zip_gcd(type, src_lat, src_long, latitude, longitude), prec) AS `distance`
        FROM communes
        WHERE codePostal != zip_start
              AND (POW((69.1 * (longitude - src_long) * COS(src_lat / 57.3)), 2) + POW((69.1 * (latitude - src_lat)), 2)) <= (radius * radius)
        ORDER BY `distance` ASC;
    END$

DELIMITER ;