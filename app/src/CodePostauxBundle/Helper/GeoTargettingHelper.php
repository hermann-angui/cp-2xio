<?php
/**
 * Created by PhpStorm.
 * User: anguidev
 * Date: 2/27/18
 * Time: 8:29 AM
 */

namespace CodePostauxBundle\Helper;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;


class GeoTargettingHelper
{
    protected $em;

    public function __construct(Container $container)
    {
        $this->em = $container->get('doctrine.orm.default_entity_manager');
    }

    /**
     *
     * @get distance from latitude and longitute
     *
     * @param float $lat_from
     *
     * @param float $long_from
     *
     * @param float $lat_to
     *
     * @param float *long_to
     *
     * @param $unit options k, m, n, Default k
     *
     * @return float
     *
    */
    function getRiemannDistance($lat_from, $long_from, $lat_to, $long_to, $unit = 'k')
    {
        /*** distance unit ***/
        switch ($unit):
            /*** miles ***/
            case 'm':
                $unit = 3963;
                break;
            /*** nautical miles ***/
            case 'n':
                $unit = 3444;
                break;
            default:
                /*** kilometers ***/
                $unit = 6371;
        endswitch;

        /*** 1 degree = 0.017453292519943 radius ***/
        $degreeRadius = deg2rad(1);

        /*** convert longitude and latitude to radians ***/
        $lat_from  *= $degreeRadius;
        $long_from *= $degreeRadius;
        $lat_to    *= $degreeRadius;
        $long_to   *= $degreeRadius;

        /*** apply the Great Circle Distance Formula ***/
        $dist = sin($lat_from) * sin($lat_to) + cos($lat_from) * cos($lat_to) * cos($long_from - $long_to);

        /*** radius of earth * arc cosine ***/
        return ($unit * acos($dist));
    }


    function getRiemannDistanceSql( $lat_from, $long_from, $lat_to, $long_to, $unit = 'k')
    {

        try
        {
            /*** distance unit ***/
            switch ($unit):
                /*** miles ***/
                case 'm':
                    $unit = 3963;
                    break;
                /*** nautical miles ***/
                case 'n':
                    $unit = 3444;
                    break;
                default:
                    /*** kilometers ***/
                    $unit = 6371;
            endswitch;

            /*** the sql ***/
            $sql = "SELECT :unit * ACOS( SIN(RADIANS(:lat_from)) * SIN(RADIANS(:lat_to)) + COS(RADIANS(:lat_from))  * COS(RADIANS(:lat_to)) * COS(RADIANS(:long_from) - RADIANS(:long_to))) AS distance";
            $params = [
                "lat_from" => $lat_from,
                "lat_to" =>   $lat_to,
                "long_from" => $long_from,
                "long_to" => $long_to
            ];

            $stmt = $this->em->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_COLUMN);
        }
        catch( \Exception $e )
        {
            return FALSE;
        }
    }


    /*
    *
    * @get cities within $distance
    *
    * @param int $latitude
    *
    * @param int $longitude
    *
    * @param int $distance, default 25
    *
    * @param int $unit, default kilomenters
    *
    * @return int
    *
    */
    function getSpacialProximity( $latitude, $longitude, $distance = 25, $unit = 'k')
    {

        try
        {
            /*** distance unit ***/
            switch ($unit):
                /*** miles ***/
                case 'm':
                    $unit = 3963;
                    break;
                /*** nautical miles ***/
                case 'n':
                    $unit = 3444;
                    break;
                default:
                    /*** kilometers ***/
                    $unit = 6371;
            endswitch;

            /*** the sql ***/
            $sql = "SELECT  id, nom, longitude, latitude, codePostal, ( :unit * ACOS( COS( RADIANS(:latitude) ) * COS( RADIANS( latitude ) ) * COS( RADIANS( longitude ) - RADIANS(:longitude) ) + SIN( RADIANS(:latitude) ) * SIN( RADIANS( latitude ) ) ) ) AS distance FROM communes HAVING distance < :distance ORDER BY distance";
            $params = [
                "latitude" => $latitude,
                "longitude" => $longitude,
                "distance" => $distance,
                "unit" => $unit
            ];

            $stmt = $this->em->getConnection()->prepare($sql);
            $stmt->execute($params);
            /*** return the distance ***/
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        catch( \Exception $e )
        {
            echo $e->getMessage();
            return FALSE;
        }
    }


    /*
     *
     * @get spacial proximity based on zip/post code
     *
     * @param int $zip
     *
     * @param int $distance
     *
     * @param int $precision
     *
     * @param int $unit, default 'K'
     *
     * @return array
     *
     */
    function getSpatialProximityByZip($zip, $distance, $precision, $unit = 'k')
    {

        try
        {
            /*** the sql ***/
            $sql = "CALL zip_radius(:unit, :zip, :distance, :precision)";
            $params = [
                "unit" => $unit,
                "zip" => $zip,
                "distance" => $distance,
                "precision" => $precision
            ];

            $stmt = $this->em->getConnection()->prepare($sql);
            $stmt->execute($params);

            /*** return the distance ***/
            return $stmt->fetchAll(\PDO::FETCH_BOTH);
        }
        catch( \Exception $e )
        {
            echo $e->getMessage();
            return false;
        }
    }


    function getAllByZip($zip)
    {

        try
        {
            /*** the sql ***/
            $sql = "SELECT id, nom, longitude, latitude, codePostal, '0' as distance FROM communes WHERE codePostal = :zip";
            $params = ["zip" => $zip,];

            $stmt = $this->em->getConnection()->prepare($sql);
            $stmt->execute($params);

            /*** return the distance ***/
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        catch( \Exception $e )
        {
            echo $e->getMessage();
            return false;
        }
    }

    function getAllByZipOrCommune($param)
    {
        try
        {
            /*** the sql ***/
            $sql = "SELECT id, nom, longitude, latitude, codePostal, '0' as distance FROM communes WHERE (codePostal LIKE :param OR nom LIKE :param)";
            $params = ["param" => "{$param}%"];

            $stmt = $this->em->getConnection()->prepare($sql);
            $stmt->execute($params);

            /*** return the distance ***/
            return $stmt->fetchAll(\PDO::FETCH_BOTH);
        }
        catch( \Exception $e )
        {
            echo $e->getMessage();
            return false;
        }
    }

    public function loadDataFromCsvFile($path)
    {
        $lines = array();
        if (file_exists($path)) {
            $fp = fopen($path, 'r');
            while ($line = fgetcsv($fp)) {
                $lines[] = $line;
            }

            return $lines;
        }
    }
}