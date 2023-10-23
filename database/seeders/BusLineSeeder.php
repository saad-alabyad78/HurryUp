<?php

namespace Database\Seeders;
use App\Enum\CityName;

use Illuminate\Database\Seeder;
use App\Models\BusLine;
use App\Models\Vertices;
use App\Models\Edges;
use Illuminate\Support\Facades\DB;
use \Grimzy\LaravelMysqlSpatial\Types\Point;

class BusLineSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('vertices')->truncate();
        DB::table('bus_lines')->truncate();
        DB::table('edges')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $busLines = [
            [
                'name' => 'مهاجرين باب توما',
                'price' => 1000,
                'city_name' => CityName::DAMASCUS,
                'bus_line' => [
                    ['name' => 'باب توما ', 'latitude' => 33.517432555178466, 'longitude' => 36.319956857436296],
                    ['name' => 'ساحة التحرير', 'latitude' => 33.51834492221091, 'longitude' => 36.31223209597805],
                    ['name' => 'اول شارع بغداد الراجع', 'latitude' => 33.51888160421708, 'longitude' => 36.31225355336982],
                    ['name' => 'اخر شارع بغداد ', 'latitude' => 33.51979395596634, 'longitude' => 36.312489587747706],
                    ['name' => 'المزرعة ', 'latitude' => 33.52124296543656, 'longitude' => 36.30120285295038],
                ],
            ],
            [
                'name' => 'الباص الاخضر',
                'price' => 1000,
                'city_name' => CityName::DAMASCUS,
                'bus_line' => [
                    ['name' => '2جسر الرئيس', 'latitude' => 33.5134817483409, 'longitude' => 36.28937938739717],
                    ['name' => 'شارع الثورة ', 'latitude' => 33.5144800352018, 'longitude' => 36.299780195605756],
                    ['name' => 'اول شارع بغداد ', 'latitude' => 33.51982886868554, 'longitude' => 36.300745219501906],
                ],
            ],
            [
                'name' => 'دوار شمالي',
                'price' => 1000,
                'city_name' => CityName::DAMASCUS,
                'bus_line' => [
                    ['name' => 'ساحة الميساة', 'latitude' => 33.52921792603257, 'longitude' => 36.294167459865776],
                    ['name' => 'ركن الدين', 'latitude' => 33.53278712320541, 'longitude' => 36.29402989407943],
                    ['name' => 'ركن الدين (الجبل)', 'latitude' => 33.53543884091568, 'longitude' => 36.29523359404133],
                ],
            ],
            [
                'name' => ' صناعة',
                'price' => 1000,
                'city_name' => CityName::DAMASCUS,
                'bus_line' => [
                    ['name' => 'اول طريق المطار ', 'latitude' => 33.504511908106686, 'longitude' => 36.3161984001115],
                    ['name' => 'همك ', 'latitude' => 33.49728997705006,  'longitude' =>36.32120350089668],
                ],
            ],
        ];

        foreach ($busLines as $index => $busLineData) {
            $busLine = new BusLine();
            $busLine->name = $busLineData['name'];
            $busLine->price = $busLineData['price'];
            $busLine->city_name = $busLineData['city_name'];
            $busLine->save();

            $verticesData = $busLineData['bus_line'];
            $previousVertex = null;

            foreach ($verticesData as $vertexData) {
                $vertex = new Vertices();
                $vertex->bus_line_id = $busLine->id;
                $vertex->point = new Point($vertexData['longitude'], $vertexData['latitude']);
                $vertex->name = $vertexData['name'];

                $vertex->save();

                if ($previousVertex) {
                    $distance = $this->calculateDistance(
                        $previousVertex->point->getLat(),
                        $previousVertex->point->getLng(),
                        $vertex->point->getLat(),
                        $vertex->point->getLng()
                    );

                    $edge = new Edges();
                    $edge->source_vertex_id = $previousVertex->id;
                    $edge->target_vertex_id = $vertex->id;

                    $edge->distance = $distance;
                    $edge->status = 'bus';
                    $edge->time = $this->calculateEstimatedTime($distance);
                    $edge->weight = $distance +   $edge->time;
                    $edge->save();
                }

                $previousVertex = $vertex;
            }


        }
        foreach ($busLines as $index => $busLineData) {
            for ($index2 = $index + 1; $index2 < count($busLines); $index2++) {
                $this->addShortestDistanceEdge($index + 1, $index2 + 1);
            }
        }

    }



    function addShortestDistanceEdge($busLineId1, $busLineId2)
    {
        $vertices1 = Vertices::where('bus_line_id', $busLineId1)->get();

        $vertices2 = Vertices::where('bus_line_id', $busLineId2)->get();

        $shortestDistance = INF;
        $shortestVertices = [];

        foreach ($vertices1 as $vertex1) {
            foreach ($vertices2 as $vertex2) {
                $distance = $this->calculateDistance(
                    $vertex1->point->getLat(),
                    $vertex1->point->getLng(),
                    $vertex2->point->getLat(),
                    $vertex2->point->getLng()
                );

                if ($distance < $shortestDistance) {
                    $shortestDistance = $distance;
                    $shortestVertices = [$vertex1, $vertex2];
                }
            }
        }

        if (!empty($shortestVertices)) {
            $edge = new Edges();
            $edge->source_vertex_id = $shortestVertices[0]->id;
            $edge->target_vertex_id = $shortestVertices[1]->id;

            $edge->distance = $shortestDistance ;
            $edge->status = 'walking2';
            $edge->time = $this->calculateWalkingTime($shortestDistance);
            $edge->weight = $shortestDistance + $edge->time;
            $edge->save();
            $edge = new Edges();
            $edge->source_vertex_id = $shortestVertices[1]->id;
            $edge->target_vertex_id = $shortestVertices[0]->id;
            $edge->distance = $shortestDistance;
            $edge->status = 'walking2';
            $edge->time = $this->calculateWalkingTime($shortestDistance);
            $edge->weight = $shortestDistance + $edge->time;
              $edge->save();
        }
    }

    function calculateWalkingTime($distance)
    {
        $walkingSpeed = 5;
        $walkingTime = $distance / $walkingSpeed;
        $walkingTimeMinutes = $walkingTime * 60;

        return $walkingTimeMinutes;
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371;

        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $deltaLat = $lat2 - $lat1;
        $deltaLon = $lon2 - $lon1;

        $angle = 2 * asin(sqrt(pow(sin($deltaLat / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($deltaLon / 2), 2)));

        return $angle * $earthRadius;
    }

    private function calculateEstimatedTime($distance)
    {
        $averageBusSpeed = 30;

        $distanceInMeters = $distance * 1000;

        $estimatedTime = $distanceInMeters / ($averageBusSpeed * 1000 / 60);

        return ceil($estimatedTime);
    }
}
