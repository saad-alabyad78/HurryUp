<?php

namespace App\Http\Controllers;

use App\Models\BusLine;
use App\Models\Vertices;
use App\Models\Edges;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EdgesController extends Controller
{


    public function index()
    {
        $edges = Edges::all();
        return response()->json($edges);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'source_vertex_id' => 'required',
            'target_vertex_id' => 'required',
            'status' => 'required',
        ]);

        $source_vertex_id = $data['source_vertex_id'];
        $target_vertex_id = $data['target_vertex_id'];

        $source_vertex = Vertices::find($source_vertex_id);
        $target_vertex = Vertices::find($target_vertex_id);

        $distance = $this->calculateDistance(
            $source_vertex->lat,
            $source_vertex->lon,
            $target_vertex->lat,
            $target_vertex->lon
        );

        $status = $data['status'];
        if ($status == 'walking2') {
            $time = $this->calculateWalkingTime($distance);
        } else {
            $time = $this->calculateEstimatedTime($distance);
        }


        $busLine = BusLine::find($target_vertex->bus_line_id);
        $price = $busLine->price ;

        $weight = $time + $busLine->price / 1000;

        $data['weight'] = $weight;
        $data['distance'] = $distance;
        $data['time'] = $time;

        $edge = Edges::create($data);
        return response()->json($edge, 201);
    }




    public function destroy(Edges $edge)
    {
        $edge->delete();
        return response()->json(null, 204);
    }


public function findShortestPath(Request $request)
{
    $sourceName = $request->input('source_name');
    $targetName = $request->input('target_name');

    $sourceVertex = Vertices::where('name', $sourceName)->first();
    $targetVertex = Vertices::where('name', $targetName)->first();

        if (!$sourceVertex || !$targetVertex) {
            return response()->json(['message' => 'One or both vertices not found'], 404);
        }

        if ( $sourceVertex->busy_at <=Carbon::now()->subHour()) {
            $sourceVertex->is_busy = false;
            $sourceVertex->busy_at = null;
            $sourceVertex->save();

           // return response()->json(" asdasdas ");
        }


        $sourceVertexId = $sourceVertex->id;
        $targetVertexId = $targetVertex->id;

        $allVertices = Vertices::all();
        $newEdgesIds = [];

        foreach ($allVertices as $vertex) {
            $distance = $this->calculateDistance(
                $vertex->lat,
                $vertex->lon,
                $targetVertex->lat,
                $targetVertex->lon
            );
            if ($distance != 0) {
                $edge = new Edges();
                $edge->source_vertex_id = $vertex->id;
                $edge->target_vertex_id = $targetVertexId;
                $edge->weight = $distance;
                $edge->distance = $distance;
                $edge->status = 'walking2';
                $edge->time = $this->calculateWalkingTime($distance);
                $edge->weight = $edge->time;
                $edge->save();

                $newEdgesIds[] = $edge->id;
            }
        }

        $path = $this->shortestPath($sourceVertexId, $targetVertexId);

       Edges::whereIn('id', $newEdgesIds)->delete();
       $newPath = [];
       foreach (range(0, count($path) - 2) as $i) {
           $vertex = Vertices::find($path[$i]);
           $vertex2 = Vertices::find($path[$i + 1]);
           $newPath[] = $path[$i];
           if ($vertex->bus_line_id != $vertex2->bus_line_id) {
               $newPath[] = -1;
           }
       }
       $newPath[] = end($path);
       $path = $newPath;
        return response()->json($path);





    //   idea
    $instructions = [];
$currentBusLine = null;
$previousLastVertex = null;

$firstVertex = Vertices::where('id', $path[0])->first();
$lastVertex = Vertices::where('id', end($path))->first();

if ($firstVertex->name === $lastVertex->name) {
    return ' alrady there ';
}

foreach ($path as $index => $vertexId) {
    $vertex = Vertices::where('id', $vertexId)->first();
    $busLine = BusLine::where('id', $vertex->bus_line_id)->first();

    if ($currentBusLine === null) {
        // First vertex
        $currentBusLine = $busLine;
        $startVertex = $vertex->name;
    } elseif ($busLine->id !== $currentBusLine->id) {
        // Switching buses
        $endVertex = $path[$index - 1];
        $endVertexName = Vertices::where('id', $endVertex)->value('name');

        if ($previousLastVertex !== null && $previousLastVertex !== $startVertex) {
            $instructions[] = "Walk from {$previousLastVertex} until you reach {$startVertex}.";
        }
        if ($endVertexName !== $startVertex) {
        $instructions[] = "Ride {$currentBusLine->name} from {$startVertex} until you reach {$endVertexName}.";
        }
        if ($endVertexName !== $vertex->name) {
            $instructions[] = "Walk from {$endVertexName} until you reach {$vertex->name}.";
        }

        $previousLastVertex = $vertex->name;
        $currentBusLine = $busLine;
        $startVertex = $vertex->name;
    }
}

$endVertex = end($path);
$endVertexName = Vertices::where('id', $endVertex)->value('name');

if ($endVertexName !== $startVertex) {
    $instructions[] = "Ride {$currentBusLine->name} from {$startVertex} until you reach {$endVertexName}.";
}

$instructionString = implode(' then ', $instructions);

return $instructionString; }



    function shortestPath($sourceVertexId, $targetVertexId)
{
    $distances = [];
    $previous = [];
    $unvisited = [];

    $vertices = Vertices::all();
    foreach ($vertices as $vertex) {
        $distances[$vertex->id] = INF;
        $previous[$vertex->id] = null;
        $unvisited[] = $vertex->id;
    }
    $distances[$sourceVertexId] = 0;

    while (!empty($unvisited)) {
        $minDistance = INF;
        $currentVertex = null;
        foreach ($unvisited as $vertexId) {
            if ($distances[$vertexId] < $minDistance) {
                $minDistance = $distances[$vertexId];
                $currentVertex = $vertexId;
            }
        }

        if ($currentVertex === null) {
            break;
        }

        $unvisited = array_diff($unvisited, [$currentVertex]);

        $edges = Edges::where('source_vertex_id', $currentVertex)->get();
        foreach ($edges as $edge) {
            $neighborId = $edge->target_vertex_id;
            $weight = $edge->weight;

            $tentativeDistance = $distances[$currentVertex] + $weight;
            if ($tentativeDistance < $distances[$neighborId]) {
                $distances[$neighborId] = $tentativeDistance;
                $previous[$neighborId] = $currentVertex;
            }
        }
    }

    $path = [];
    $currentVertex = $targetVertexId;
    while ($currentVertex !== $sourceVertexId) {
        $path[] = $currentVertex;
        $currentVertex = $previous[$currentVertex];
        if ($currentVertex === null) {
            break;
        }
    }
    $path[] = $sourceVertexId;


    $path = array_reverse($path);

    return $path;
}

//   A*
// function shortestPath($sourceVertexId, $targetVertexId)
// {
//     $distances = [];
//     $previous = [];
//     $unvisited = [];

//     $vertices = Vertices::all();
//     foreach ($vertices as $vertex) {
//         $distances[$vertex->id] = INF;
//         $previous[$vertex->id] = null;
//         $unvisited[] = $vertex->id;
//     }
//     $distances[$sourceVertexId] = 0;

//     while (!empty($unvisited)) {
//         $minDistance = INF;
//         $currentVertex = null;

//         // Find the vertex with the lowest total distance (including heuristics)
//         foreach ($unvisited as $vertexId) {
//             $distance = $distances[$vertexId] + $this->heuristic($vertexId, $targetVertexId);
//             if ($distance < $minDistance) {
//                 $minDistance = $distance;
//                 $currentVertex = $vertexId;
//             }
//         }

//         if ($currentVertex === null) {
//             break;
//         }

//         $unvisited = array_diff($unvisited, [$currentVertex]);

//         $edges = Edges::where('source_vertex_id', $currentVertex)->get();
//         foreach ($edges as $edge) {
//             $neighborId = $edge->target_vertex_id;
//             $weight = $edge->weight;

//             $tentativeDistance = $distances[$currentVertex] + $weight;
//             if ($tentativeDistance < $distances[$neighborId]) {
//                 $distances[$neighborId] = $tentativeDistance;
//                 $previous[$neighborId] = $currentVertex;
//             }
//         }
//     }

//     $path = [];
//     $currentVertex = $targetVertexId;
//     while ($currentVertex !== $sourceVertexId) {
//         $path[] = $currentVertex;
//         $currentVertex = $previous[$currentVertex];
//         if ($currentVertex === null) {
//             break;
//         }
//     }
//     $path[] = $sourceVertexId;

//     $path = array_reverse($path);

//     return $path;
// }

// function heuristic($vertexId, $targetVertexId)
// {

//     $vertex = Vertices::where('id', $vertexId)->first();
//     $targetVertex = Vertices::where('id', $targetVertexId)->first();

//     // Calculate Euclidean distance between the vertices as a heuristic estimate
//     $heuristic = sqrt(($vertex->x - $targetVertex->x) ** 2 + ($vertex->y - $targetVertex->y) ** 2);

//     return $heuristic;
// }





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




