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
                            @isset($TopicnGroup)
                                <input type="hidden" name="id" value="{{ $TopicnGroup->id }}">
                            @endisset
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="">Topic Name</label>
                                    <input type="text" class="form-control" placeholder="Enter Topic Name"
                                        name="title" value="{{ isset($TopicnGroup) ? $TopicnGroup->title : old('title') }}">
                                    @error('title')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                                                      
                                </div>
                                <div class="card-body">
                                <div class="form-group">
                                    <label for="">Topic Description</label>
                                    <input type="text" class="form-control" placeholder="Enter Topic Description"
                                        name="description" value="{{ isset($TopicnGroup) ? $TopicnGroup->description : old('description') }}">
                                    @error('description')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                    
                                   
                                </div>
                                <div class="card-body">
                                <div class="form-group">
                                <label for="image">Image</label>
                                <input type="file" class="form-control" name="image">
                                   @error('image')
                             <div class="text-danger">{{ $message }}</div>
                                 @enderror
                                 </div>
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
