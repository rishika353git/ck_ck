<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TopicnGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TopicsandGroupController extends Controller
{
    public function index()
    {
        $TopicnGroups = TopicnGroup::get();
        return view('TopicnGroups.index', compact('TopicnGroups'));

    }
    public function create(Request $request)
    {
        $heading = "Create New Topic";
        $btntext = "Create";
        $actionurl = route('TopicnGroups.store');
        return view('TopicnGroups.store', compact('heading', 'btntext', 'actionurl'));
    }
    public function store(Request $request)
    {
       // dd($request);
        $request->validate([
            'title' => 'required',
            'description' => 'required',
            'image' => 'required|image|mimes:png|max:5120',           
        
        ]);

        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'image' => $request->file('image')->store('uploads', 'public'),
        ];
//dd('before handle image');
           // Handle image upload
       if ($request->hasFile('image')) {
        // Store the image in the 'images' folder within the public storage
        $imagePath = $request->file('image')->store('uploads', 'public');
        $data['image'] = $imagePath; // Save the image path to the database
    }
  //  dd(' handle image');
        DB::beginTransaction();
        try {

            Log::info('Attempting to create a new Topic', $data);
            $TopicnGroup = TopicnGroup::create($data);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $e->getMessage();
            $TopicnGroup = null;
        }

        if ($TopicnGroup != null) {
            return redirect()->route('TopicnGroups')->with('success', 'Topic Added Successfully');
        } else {
            return back()->with('error', 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function edit($id){
        $TopicnGroup = TopicnGroup::findOrFail($id);
        $heading = "Update Topic";
        $btntext = "Update";
        $actionurl = route('TopicnGroups.update');
        return view('TopicnGroups.store', compact('heading', 'btntext', 'actionurl','TopicnGroup'));
    }

    public function update(Request $request,) {
        $id = $request->input('id');
        $validatedData = $request->validate([
            'name' => 'required',
        ]);
        $TopicnGroup = TopicnGroup::findOrFail($id);
        $TopicnGroup->name = $validatedData['name'];
        $TopicnGroup->save();
        return redirect()->route('TopicnGroups')->with('success', 'Topic Updated Successfully');

    }

    public function disable($id)
    {

        // Your deletion logic here
        $TopicnGroups = TopicnGroup::findOrFail($id);
            $TopicnGroups->status = '0';
            $TopicnGroups->save();

            if($TopicnGroups){
                return redirect()->route('TopicnGroups')->with('delete', 'Topic Disable Successfully');
            }else{
                return redirect()->route('TopicnGroups')->with('info', 'Internal Serve Error');
            }
    

        // Redirect or return response
    }
    public function enable($id)
    {

        // Your deletion logic here
        $TopicnGroups = TopicnGroup::findOrFail($id);
            $TopicnGroups->status = '1';
            $TopicnGroups->save();

            if($TopicnGroups){
                return redirect()->route('TopicnGroups')->with('success', 'Topic Enable Successfully');
            }else{
                return redirect()->route('TopicnGroups')->with('info', 'Internal Serve Error');
            }
     

        // Redirect or return response
    }
}
