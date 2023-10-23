<?php

namespace App\Http\Controllers;

use App\Models\BusLine;
use Illuminate\Http\Request;
use App\Models\Vertices;
use App\Models\Edges;
use \Grimzy\LaravelMysqlSpatial\Types\Point;
class BusLineController extends Controller
{

    public function index()
{
    $busLines = BusLine::all();
    return response()->json($busLines);
}

public function store(Request $request)
{
    $busLineData = $request->validate([
        'name' => 'required',
        'price' => 'required',
        'city_name' => 'required',
        'bus_line' => 'required|array',

    ]);

    $busLine = new BusLine();
    $busLine->name = $busLineData['name'];
    $busLine->price = $busLineData['price'];
    $busLine->city_name = $busLineData['city_name'];
    $busLine->save();
    $verticesData = $busLineData['bus_line'];
    $previousVertex = null;
    $firstVertex = null;

    foreach ($verticesData as $vertexData) {
        $vertex = new Vertices();
        $vertex->bus_line_id  = $busLine->id;
        $vertex->name = $vertexData['name'];
        $vertex->point = new Point($vertexData['longitude'], $vertexData['latitude']);
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
            $edge->weight = $edge->time + $busLine->price / 1000;
            $edge->save();
        } else {
            $firstVertex = $vertex;
        }

        $previousVertex = $vertex;
    }

    // Add an edge between the final vertex and the first vertex
    if ($previousVertex && $firstVertex) {
        $distance = $this->calculateDistance(
            $previousVertex->point->getLat(),
            $previousVertex->point->getLng(),
            $firstVertex->point->getLat(),
            $firstVertex->point->getLng()
        );

        $edge = new Edges();
        $edge->source_vertex_id = $previousVertex->id;
        $edge->target_vertex_id = $firstVertex->id;
        $edge->distance = $distance;
        $edge->status = 'bus';
        $edge->time = $this->calculateEstimatedTime($distance);
        $edge->weight = $edge->time + $busLine->price / 1000;
        $edge->save();
    }
    $busLines = Busline::all();
    foreach ($busLines as $index => $busLineData) {
        if ($busLine->id != $index + 1 && $busLine->city_name === $busLineData->city_name) {
            $this->addShortestDistanceEdge($busLine->id, $index + 1);
        }
    }

    return response()->json($busLine, 201);
}



public function destroy(Request $request)
{
    $busLineId = $request->input('bus_line_id');
    $busLine = BusLine::find($busLineId);

    if ($busLine) {
        $busLine->delete();
        return response()->json(['message' => 'BusLine deleted successfully'], 200);
    } else {
        return response()->json(['message' => 'BusLine not found'], 404);
    }
}


function addShortestDistanceEdge($busLineId1, $busLineId2)
{
    $vertices1 = Vertices::where('bus_line_id', $busLineId1)->get();

    $vertices2 = Vertices::where('bus_line_id', $busLineId2)->get();

    if ($vertices1->isEmpty() || $vertices2->isEmpty()) {
        // Handle the case when no vertices are found
        $busLineIds = [];
        if ($vertices1->isEmpty()) {
            $busLineIds[] = $busLineId1;
        }
        if ($vertices2->isEmpty()) {
            $busLineIds[] = $busLineId2;
        }
        return response()->json(['message' => 'No vertices found for the given bus line IDs', 'busLineIds' => $busLineIds], 404);
    }
    $busLine = Busline::where('id', $busLineId2)->first();
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


        $edge = new Edges();
        $edge->source_vertex_id = $shortestVertices[0]->id;
        $edge->target_vertex_id = $shortestVertices[1]->id;

        $edge->distance = $shortestDistance ;
        $edge->status = 'walking2';
        $edge->time = $this->calculateWalkingTime($shortestDistance);
        $edge->weight = $edge->time +  $busLine->price/500   ;
        $edge->save();
        $edge = new Edges();
        $edge->source_vertex_id = $shortestVertices[1]->id;
        $edge->target_vertex_id = $shortestVertices[0]->id;
        $edge->distance = $shortestDistance;
        $edge->status = 'walking2';
        $edge->time = $this->calculateWalkingTime($shortestDistance);
        $edge->weight =   $edge->time +   $busLine->price/500   ;
          $edge->save();

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

