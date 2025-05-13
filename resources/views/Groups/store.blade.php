@extends('layouts.main')
@section('main-section')
    <section class="content">
        <div class="container-fluid">
            <!-- Alert Messages -->
            @if (Session::has('success'))
                <script>
                    swal("success", "{{ Session::get('success') }}", "success", {
                        button: true,
                        button: "OK",
                        //timer: 3000,
                        dangerMode: true,
                    });
                </script>
            @elseif (Session::has('delete'))
                <script>
                    swal("success", "{{ Session::get('delete') }}", "success", {
                        button: true,
                        button: "OK",
                        //timer: 3000,
                        dangerMode: true,
                    });
                </script>
                @elseif(Session::has('error'))
                <script>
                    swal("error", "{{ Session::get('error') }}", "info", {
                        button: true,
                        button: "OK",
                        //timer: 3000,
                        dangerMode: true,
                    });
                </script>
            @endif

            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">{{ $heading }}</h3>
                        </div>
                        <form action="{{ $actionurl }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <!-- If $Court exists, include its ID -->
                            @isset($Group)
                                <input type="hidden" name="id" value="{{ $Group->id }}">
                            @endisset
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="">Group Name</label>
                                    <input type="text" class="form-control" placeholder="Enter Group Name"
                                        name="name" value="{{ isset($Group) ? $Group->name : old('name') }}">
                                    @error('name')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                                                      
                                </div>
                                <div class="card-body">
                                <div class="form-group">
                                    <label for="">joinedMembers</label>
                                    <input type="text" class="form-control" placeholder="Enter joinedMembers id "
                                        name="joinedMembers" value="{{ isset($Group) ? $Group->joinedMembers : old('joinedMembers') }}">
                                    @error('joinedMembers')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    
                                   
                                </div>

                                <div class="card-body">
                                <div class="form-group">
                                <label for="logo">logo</label>
                                <input type="file" class="form-control" name="logo">
                                   @error('logo')
                             <div class="text-danger">{{ $message }}</div>
                                 @enderror
                                 </div>

                                <div class="card-body">
                                <div class="form-group">
                                    <label for="">description</label>
                                    <input type="text" class="form-control" placeholder="Enter description "
                                        name="description" value="{{ isset($Group) ? $Group->description : old('description') }}">
                                    @error('description')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    
                                   
                                </div>
                               
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">{{ $btntext }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
