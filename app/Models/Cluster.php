<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cluster
{
    use HasFactory;

    public static function kmeans(array $dataPoints, int $k, int $maxIterations = 100): array
    {
        // Initialize centroids randomly
        $centroids = array_rand($dataPoints, $k);

        for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
            $clusters = array_fill(0, $k, []);

            foreach ($dataPoints as $index => $dataPoint) {
                $minDistance = INF;
                $nearestCluster = null;

                foreach ($centroids as $clusterIndex => $centroidIndex) {
                    $centroid = $dataPoints[$centroidIndex];
                    $distance = self::calculateDistance($dataPoint, $centroid);

                    // Pernyataan debugging untuk mencetak nilai
                    echo "Titik Data: $index, Sentroid: $centroidIndex, Jarak: $distance\n";

                    if ($distance < $minDistance) {
                        $minDistance = $distance;
                        $nearestCluster = $clusterIndex;
                    }
                }

                $clusters[$nearestCluster][] = $index;
            }


            $newCentroids = [];
            foreach ($clusters as $cluster) {
                // Calculate new centroids based on the data points in the cluster
                $clusterDataPoints = array_intersect_key($dataPoints, array_flip($cluster));
                $newCentroids[] = self::calculateCentroid($clusterDataPoints);
            }

            // Check for convergence
            if ($newCentroids === $centroids) {
                break;
            }

            $centroids = $newCentroids;
        }

        return $clusters;
    }

    private static function calculateDistance(array $point1, array $point2): float
    {
        $squaredSum = 0;

        foreach ($point1 as $dimension => $value) {
            if (is_numeric($value) && is_numeric($point2[$dimension])) {
                $squaredSum += pow($value - $point2[$dimension], 2);
            } else {
                // Handle the case where values are not numeric (e.g., skip or throw an error)
            }
        }

        return sqrt($squaredSum);
    }


    private static function calculateCentroid(array $dataPoints): array
    {
        $centroid = [];

        $numDataPoints = count($dataPoints);

        if ($numDataPoints === 0) {
            return $centroid;
        }

        $numDimensions = count($dataPoints[0]);

        for ($dimension = 0; $dimension < $numDimensions; $dimension++) {
            $sum = 0;
            $validDataPoints = 0; // Count valid data points for each dimension

            foreach ($dataPoints as $dataPoint) {
                if (isset($dataPoint[$dimension])) { // Check if index exists
                    $sum += $dataPoint[$dimension];
                    $validDataPoints++;
                }
            }

            if ($validDataPoints > 0) {
                $centroid[$dimension] = $sum / $validDataPoints; // Use validDataPoints for division
            } else {
                $centroid[$dimension] = 0; // Default value when no valid data points found
            }
        }

        return $centroid;
    }
}
