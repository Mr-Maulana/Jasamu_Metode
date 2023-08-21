<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Review;
use App\Models\DailyReport;
use App\Models\Cluster;
use Illuminate\Support\Facades\DB;

class ClusteringController extends Controller
{
    public function clusterServices()
    {
        $services = Service::select('id', 'name', 'category')
            ->withCount('reviews')
            ->get();

        $serviceReviews = Review::select('service_id', DB::raw('AVG(rating) as average_rating'))
            ->groupBy('service_id')
            ->get();

        $interactions = DailyReport::select('service_id', DB::raw('SUM(interactions) as total_interactions'))
            ->groupBy('service_id')
            ->get();

        $normalizedServices = [];
        foreach ($services as $service) {
            $averageRating = $serviceReviews->where('service_id', $service->id)->first()->average_rating ?? 0;

            $interactionData = $interactions->where('service_id', $service->id)->first();
            $normalizedInteractions = $interactionData
                ? ($interactionData->total_interactions - $interactions->min('total_interactions')) / ($interactions->max('total_interactions') - $interactions->min('total_interactions'))
                : 0;

            $normalizedServices[] = [
                'id' => $service->id,
                'name' => $service->name,
                'category' => $service->category,
                'normalized_rating' => $averageRating,
                'normalized_interactions' => $normalizedInteractions,
            ];
        }

        $clusters = Cluster::kmeans($normalizedServices, 4);

        $mostPopular = [];
        $popular = [];
        $lessPopular = [];
        $leastPopular = [];

        foreach ($clusters as $cluster => $clusterData) {
            $averageRating = $clusterData['centroid']['normalized_rating'];
            $averageInteractions = $clusterData['centroid']['normalized_interactions'];

            if ($averageRating >= 0.8 && $averageInteractions >= 1.0) {
                $mostPopular[] = $cluster;
            } elseif ($averageRating >= 0.5 && $averageInteractions >= 0.7) {
                $popular[] = $cluster;
            } elseif ($averageRating >= 0.1 && $averageInteractions >= 0.4) {
                $lessPopular[] = $cluster;
            } else {
                $leastPopular[] = $cluster;
            }
        }

        return view('clustered-services', [
            'services' => $normalizedServices,
            'mostPopularCategories' => $mostPopular,
            'popularCategories' => $popular,
            'lessPopularCategories' => $lessPopular,
            'leastPopularCategories' => $leastPopular,
        ]);
    }
}
