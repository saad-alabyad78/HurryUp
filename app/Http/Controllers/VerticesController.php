<?php

namespace App\Http\Controllers;

use App\Models\Vertices;
use App\Models\Edges;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Http\Response;

class VerticesController extends Controller
{

    public function index()
    {
        $vertices = Vertices::all();
        return response()->json($vertices);
    }



//    public function update(Request $request, Vertices $vertices)
// {

//     $data = $request->validate([
//         'bus_line_id' => 'required',
//         'point' => 'required',
//         'is_busy' => 'required',
//         'name' => 'required'
//     ]);
//     $data['point'] = json_encode($data['point']);

//     $vertices->fill($data);
//     $vertices->save();

//     $edges = Edges::where('source_vertex_id', $vertices->id)
//         ->orWhere('target_vertex_id', $vertices->id)
//         ->get();

//         foreach ($edges as $edge) {
//             $sourceVertex = Vertices::findOrFail($edge->source_vertex_id);
//             $targetVertex = Vertices::findOrFail($edge->target_vertex_id);

//             $distance = $this->calculateDistance(
//                 $sourceVertex->point->getLat(),
//                 $sourceVertex->point->getLng(),
//                 $targetVertex->point->getLat(),
//                 $targetVertex->point->getLng()
//             );

//             if ($edge->status === 'bus') {
//                 $edge->distance = $distance;
//                 $edge->time = $this->calculateEstimatedTime($distance);
//                 $edge->weight = $edge->time + $busLine->price / 1000;
//             } else {
//                 $edge->distance = $distance;
//                 $edge->time = $this->calculateWalkingTime($distance);
//                 $edge->weight = $edge->time;
//             }

//             $edge->save();
//         }

//         return response()->json($vertices, 200);

// }

public function destroy(Request $request)    {
    $verticesId = $request->input('id');
    $vertices = Vertices::find($verticesId);

    if ($vertices) {
        $vertices->delete();
        return response()->json(['message' => 'vertices deleted successfully'], 200);
    } else {
        return response()->json(['message' => 'BusLverticesine not found'], 404);
    }


    }







public function feedback(Request $request)
{

    $vertexId = $request->input('vertex_id');
    $vertex = Vertices::find($vertexId);

    if ($vertex) {
        $vertex->increment('feedback_count');

        if ($vertex->feedback_count >= 3) {
            $vertex->is_busy = true;
            $vertex->feedback_count = 0;
            $vertex->busy_at = Carbon::now();
        }

        $vertex->save();

        $previousVertex = Vertices::where('id', '<', $vertexId)->orderBy('id', 'desc')->first();
        $nextVertex = Vertices::where('id', '>', $vertexId)->orderBy('id')->first();

      if ($previousVertex && $previousVertex->bus_line_id === $vertex->bus_line_id)  {
            $previousVertex->increment('feedback_count');

            if ($previousVertex->feedback_count >= 3) {
                $previousVertex->is_busy = true;
                $previousVertex->feedback_count = 0;
                $previousVertex->busy_at = Carbon::now();
            }

            $previousVertex->save();

        }

        if ($nextVertex &&  $nextVertex->bus_line_id === $vertex->bus_line_id) {
            $nextVertex->increment('feedback_count');

            if ($nextVertex->feedback_count >= 3) {
                $nextVertex->is_busy = true;
                $nextVertex->feedback_count = 0;
                $nextVertex->busy_at = Carbon::now();
            }

            $nextVertex->save();

        }

        return response()->json(['message' => 'Vertexs updated successfully'], Response::HTTP_OK);
    }

    return response()->json(['message' => 'Vertex not found'], Response::HTTP_NOT_FOUND);

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
