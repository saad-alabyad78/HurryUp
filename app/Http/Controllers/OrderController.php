<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
    use Illuminate\Http\Response;
    use App\Models\Vertices;
        use Illuminate\Support\Str;
class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $orders = Order::all();

        return response()->json($orders);
    }



    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'top_passenger_count' => 'integer',
                'current_passenger_count' => 'integer',
                'destination_vertices_id' => 'required|integer',
                'is_hurry' => 'boolean',
                'genders' => 'in:Male,Female,both'
            ]);

            $validatedData['user_ids'] = [Auth::id()];

            $order = new Order;
            $order->top_passenger_count = $validatedData['top_passenger_count'];
            $order->current_passenger_count = $validatedData['current_passenger_count'];
            $order->destination_vertices_id = $validatedData['destination_vertices_id'];
            $order->is_hurry = $validatedData['is_hurry'];
            $order->genders = $validatedData['genders'];

            $order->user_ids = json_encode($validatedData['user_ids']);
            $order->save();

            return response()->json($order, 201);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Failed to create the order.'], 500);
        }
    }





    public function joinOrder(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'order_id' => 'required|exists:orders,id',
                'num_of_people' => 'required|integer|min:1',
            ]);

            $orderId = $validatedData['order_id'];
            $numOfPeople = $validatedData['num_of_people'];

            $order = Order::findOrFail($orderId);


               $userId = Auth::id();
            // $userId = 2;

             $userIds = json_decode($order->user_ids, true);

             if (is_array($userIds) && in_array($userId, $userIds)) {
                 return response()->json("You are already in this order", 400);
             }
            if($order->status ==="Completed")
            {                return response()->json("The order is alrady completed ", 400);
        }
            if ($order->current_passenger_count + $numOfPeople > 4) {
                return response()->json("There is no room for {$numOfPeople} more people.", 400);
            }





            $userIds = json_decode($order->user_ids, true);
            $userIds[] = $userId;

            $order->user_ids = json_encode($userIds);
            $order->increment('current_passenger_count', $numOfPeople);

            if ($order->top_passenger_count <= $order->current_passenger_count) {
                $order->status = "Completed";
            $order->save();
            // order a car api
            return response()->json("There is a car on its way ");


            }

            $order->save();

            return response()->json($order, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to join the order.'], 500);
        }
    }

    public function completeOrder(Request $request)
    {
        $orderId = $request->input('id');
        $order = Order::findOrFail($orderId);
        if(!$order->is_hurry)
        {

            return response()->json("the order tybe is not hurry") ;
        }
        $userIds = json_decode($order->user_ids, true);

        if (Auth::id() == $userIds[0]) {
            $order->status = "Completed";
            $order->save();

            // order a car api

            return response()->json("There is a car on its way");
        } else {
            return response()->json([
                'user_ids' => $userIds,
                'auth_id' => Auth::id()
            ]);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $orderId = $request->input('id');
            $order = Order::findOrFail($orderId);
            $userIds = json_decode($order->user_ids, true);

            if ( Auth::id() == $userIds[0]) {
                $order->delete();
                return response()->json('Order has been deleted successfully');
            } else {
                return response()->json('You are not authorized to delete this order', 403);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete the order.'], 500);
        }
    }





    public function gethistory()
{
    $id = Auth::id();
    $orders = Order::whereJsonContains('user_ids', $id)
    ->where('status', 'Completed')
    ->get();
    return $orders;
}


public function getPendingOrders()
{
    $orders = Order::where('status', 'pending')->get();

    return $orders;
}

public function getOrdersBydestnaion(Request $request)
{


    try {
        $validatedData = $request->validate([
            'id' => 'required|integer',
        ]);

        $id = $validatedData['id'];
        $orders = Order::where('status', 'pending')->where('destination_vertices_id', $id)->get();

        return response()->json($orders, 200);
    } catch (ValidationException $e) {
        return response()->json(['error' => $e->errors()], 400);
    } catch (Exception $e) {
        return response()->json(['error' => 'Failed to retrieve orders.'], 500);
    }
}

public function getOrdersBygender(Request $request)
{
    try {
        $validatedData = $request->validate([
            'gender' => 'required|in:Male,Female,both',
        ]);

        $gender = $validatedData['gender'];
        $orders = Order::where('status', 'pending')->where('genders', $gender)->get();

        return response()->json($orders, 200);
    } catch (ValidationException $e) {
        return response()->json(['error' => $e->errors()], 400);
    } catch (Exception $e) {
        return response()->json(['error' => 'Failed to retrieve orders.'], 500);
    }
}


public function search(Request $request)
{
    $validatedData = $request->validate([
        'name' => 'required'
    ]);

    $subname = $validatedData['name'];
    if (empty($subname)) {
        return response()->json([]);
    }
    $vertices = Vertices::all();

    foreach ($vertices as $vertex) {
        $name = $vertex->name;
        $relevanceScore = $this->calculateRelevanceScore($subname, $name);
        $vertex->relevanceScore = $relevanceScore;
    }

    $filteredVertices = $vertices->filter(function ($vertex) {
        return $vertex->relevanceScore >= 99;
    });

    $sortedVertices = $filteredVertices->sortByDesc('relevanceScore');

    $mostRelevantVertices = $sortedVertices->take(2)->pluck('name');

    return response()->json($mostRelevantVertices);
}

function calculateRelevanceScore($name, $subname)
{
    $nameLength = mb_strlen($name);
    $subnameLength = mb_strlen($subname);

    $matchedCharacters = similar_text($name, $subname);

    $relevanceScore = ($matchedCharacters / $nameLength) * 100;

    return $relevanceScore;
}


}
