<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class GroupsController extends Controller
{
    public function index()
    {
        $Groups = Group::get();
        return view('Groups.index', compact('Groups'));

    }
    public function create(Request $request)
    {
        $heading = "Create New Group";
        $btntext = "Create";
        $actionurl = route('Groups.store');
        return view('Groups.store', compact('heading', 'btntext', 'actionurl'));
    }
    public function store(Request $request)
    {
       // dd($request);
        $request->validate([
            'name' => 'required',
            'joinedMembers' => 'required',
            'logo' => 'required|image|mimes:png|max:5120',        
            'description' => 'required',
                  
        ]);

        $joinedMembers = [];
        if ($request->filled('joinedMembers')) {
          
            $joinedMembers = json_decode($request->input('joinedMembers'), true);  // Array of user IDs
            $data['joinedMembers'] = $joinedMembers;
        }
        $data= array_filter($data, function ($value) {
            return !is_null($value);
        });
        
        $data = [
            'name' => $request->name,
            'joinedMembers' => $joinedMembers,
            'logo' => $request->file('logo')->store('uploads', 'public'),
            'description' => $request->description,
        ];
        if ($request->hasFile('logo')) {
            // Store the image in the 'images' folder within the public storage
            $imagePath = $request->file('logo')->store('uploads', 'public');
            $data['logo'] = $imagePath; // Save the image path to the database
        }
        //dd($request->joinedMembers);
        //dd('before handle image');
           // Handle image upload

    //    if ($request->hasFile('image')) {
    //     // Store the image in the 'images' folder within the public storage
    //     $imagePath = $request->file('image')->store('uploads', 'public');
    //     $data['image'] = $imagePath; // Save the image path to the database
    // }

  //  dd(' handle image');
        DB::beginTransaction();
        try {

            Log::info('Attempting to create a new Group', $data);
            $Group = Group::create($data);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $e->getMessage();
            $Group = null;
        }

        if ($Group != null) {
            return redirect()->route('Groups')->with('success', 'Group Added Successfully');
        } else {
            return back()->with('error', 'Internal Server Error: ' . $e->getMessage());
        }
    }

    public function edit($id){
        $Group = Group::findOrFail($id);
        $heading = "Update Topic";
        $btntext = "Update";
        $actionurl = route('Groups.update');
        return view('Groups.store', compact('heading', 'btntext', 'actionurl','Group'));
    }

    public function update(Request $request,) {
        $id = $request->input('id');
        $validatedData = $request->validate([
            'name' => 'required',
        ]);
        $Group = Group::findOrFail($id);
        $Group->name = $validatedData['name'];
        $Group->save();
        return redirect()->route('Groups')->with('success', 'Groups Updated Successfully');

    }

    public function disable($id)
    {

        // Your deletion logic here
        $Groups = Group::findOrFail($id);
            $Groups->status = '0';
            $Groups->save();

            if($Groups){
                return redirect()->route('Groups')->with('delete', 'Group Disable Successfully');
            }else{
                return redirect()->route('Groups')->with('info', 'Internal Serve Error');
            }
    

        // Redirect or return response
    }
    public function enable($id)
    {

        // Your deletion logic here
        $Groups = Group::findOrFail($id);
            $Groups->status = '1';
            $Groups->save();

            if($Groups){
                return redirect()->route('Groups')->with('success', 'Group Enable Successfully');
            }else{
                return redirect()->route('Groups')->with('info', 'Internal Serve Error');
            }
     

        // Redirect or return response
    }

    public function delete($id)
    {

        // Your deletion logic here
        $Groups = Group::findOrFail($id)->delete();

        if($Groups){
            return redirect()->route('Groups')->with('delete', 'Group delete Successfully');
        }else{
            return redirect()->route('Groups')->with('info', 'Internal Serve Error');
        }
    }

        // public function profile($id) {
        //     // Fetch user data
        //     $Groups = DB::table('groups')
        //     ->join('users', 'groups.joinedMembers', '=', 'users.id') 
        //     ->select('users.user_roll', 'users.id as user_id', 'users.name')// Select specific fields
        //    ->where('users.id', $id) // Filter by the user ID
        //    ->first();
        
        
        //    dd($Groups);
        //    // Return the profile view with the fetched data
        //    return view('Groups.index', compact('Groups'));
        // }
        
        public function profile($id) {
            // Fetch the specific group by its ID
            $group = Group::find($id);
    
            // Check if joinedMembers is already an array
            $joinedMembers = $group->joinedMembers; 
          //  dd($group);
        //  dd($joinedMembers);  joined members showing
            if (is_array($joinedMembers) && count($joinedMembers) > 0) {
                // Fetch the users whose IDs match the joinedMembers array
                $users = User::whereIn('id', $joinedMembers)->get();
            } else {
                 // Empty collection if no members are joined
                $users = collect();
            }
    
           //passing  groups to profile view
            return view('Groups.profile', compact('group', 'users'));
        }

        //for removing user from group

//  public function removeUser($groupId, $userId)
//         {
//             $group = Group::find($groupId);
//             $user = User::find($userId);
        
//             if ($group && $user) {
        
//                 $group->users()->detach($userId);
        
//                 return redirect()->back()->with('success', 'User removed successfully.');
//             }
        
//             return redirect()->back()->with('error', 'User or Group not found.');
//         }
        
public function removeUser($groupId, $userId)
{
    // Find the group by its ID
    $group = Group::find($groupId); //$group contains groups tabble id and $userId is the id of user to be removed

    // Ensure the group exists and has joinedMembers
    if ($group && is_array($group->joinedMembers)) {
        // Check if the user ID exists in the joinedMembers array
        if (($key = array_search($userId, $group->joinedMembers)) !== false) {
            // Remove the user ID from the joinedMembers array
            $joinedMembers = $group->joinedMembers; 
             // Remove the user from the array
            unset($joinedMembers[$key]); 

            // Reindex the array to remove gaps
            $joinedMembers = array_values($joinedMembers);

            // Set the modified array back to the group model's joinedMembers attribute
            $group->joinedMembers = $joinedMembers;

            // Save the group with the updated joinedMembers array
            $group->save();

            return redirect()->back()->with('success', 'User removed successfully.');
        }

        return redirect()->back()->with('error', 'User not found in the group.');
    }

    return redirect()->back()->with('error', 'Group not found.');
}

}
