<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FileController extends Controller
{
    public function uploadImage(Request $request)
    {
        // Validate the request
        $request->validate([
            'task_id' => 'required',
            'status' => 'required',
            'files' => 'required|image|mimes:jpeg,png,jpg,gif', // Adjust the validation rules as needed
        ]);

        $task_id = $request->task_id;

        // Get the file from the request
        $files = $request->files;
        foreach ($files as $file) {
            // Generate a unique filename
            $filename = time() . '_' . $file->getClientOriginalName();

            // Define the path to save the file
            $path = storage_path("app/private/images"); // Change this to your desired local folder path

            // Create the directory if it doesn't exist
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }

            // Move the uploaded file to the specified path
            $file->move($path, $filename);

            DB::table('image')->where('task_id', $task_id)->update([
                'name' => $filename
            ]);
            // DB::table('image')->insert([
            //     'task_id' => $task_id,
            //     'user_id' => 1,
            //     'url' => $url
            // ]);
            return response()->json($file);
        }

        // Optionally, you can store the file path in your database
        // Example: File::create(['path' => $filename]);

        return response()->json(['message' => 'Image uploaded successfully']);
    }

    public function getImage($task_id)
    {
        $image = DB::table('image')->where('task_id', $task_id)->first();

        $path = storage_path('app/private/images/' . $image->name);

        if (file_exists($path)) {
            return response()->file($path);
        }

        return response()->json(['message' => 'File not found'], 404);
    }
}