<div class='card'>
    <div class='card-body'>
        <form method='POST' action='{{ route('settings.update', [strtolower($type)]) }}' class="form ">
            @csrf
            <div class='row'>
                <div class="col-12">
                    <h5 class="mb-2">For Student Dashboard</h5>
                    <div class="mb-1">
                        <label class="form-label required" for="featured_images_student_image">Image</label>
                        <div class="input-group">
                            <span class="input-group-btn">
                                <a id="lfm_featured_image_student" data-input="featured_images_student_image" data-preview="holder_student" class="btn btn-info" tabindex="1">
                                    <i class="fa fa-picture-o"></i> Choose
                                </a>
                            </span>
                            <input id="featured_images_student_image" class="form-control" type="text" name="featured_images[student][image]"
                                   value="{{ old('featured_images.student.image') ?? ($settings['featured_images']['student']['image'] ?? '') }}" required/>
                        </div>
                        <div id="holder_student" style="margin-top:15px;max-height:100px;"></div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label required" for="featured_images_student_link">Link</label>
                        <input id="featured_images_student_link" class="form-control" type="text" name="featured_images[student][link]"
                               value="{{ old('featured_images.student.link') ?? ($settings['featured_images']['student']['link'] ?? '') }}" required/>
                    </div>
                    <div class="mb-1">
                        <label class="form-label required" for="featured_images_student_title">Title</label>
                        <input id="featured_images_student_title" class="form-control" type="text" name="featured_images[student][title]"
                               value="{{ old('featured_images.student.title') ?? ($settings['featured_images']['student']['title'] ?? '') }}" required/>
                    </div>
                </div>
                <hr class="my-2">
                <div class="col-12">
                    <h5 class="mb-2">For Leader Dashboard</h5>
                    <div class="mb-1">
                        <label class="form-label required" for="featured_images_leader_image">Image</label>
                        <div class="input-group">
                            <span class="input-group-btn">
                                <a id="lfm_featured_image_leader" data-input="featured_images_leader_image" data-preview="holder_leader" class="btn btn-info" tabindex="1">
                                    <i class="fa fa-picture-o"></i> Choose
                                </a>
                            </span>
                            <input id="featured_images_leader_image" class="form-control" type="text" name="featured_images[leader][image]"
                                   value="{{ old('featured_images.leader.image') ?? ($settings['featured_images']['leader']['image'] ?? '') }}" required/>
                        </div>
                        <div id="holder_leader" style="margin-top:15px;max-height:100px;"></div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label required" for="featured_images_leader_link">Link</label>
                        <input id="featured_images_leader_link" class="form-control" type="text" name="featured_images[leader][link]"
                               value="{{ old('featured_images.leader.link') ?? ($settings['featured_images']['leader']['link'] ?? '') }}" required/>
                    </div>
                    <div class="mb-1">
                        <label class="form-label required" for="featured_images_leader_title">Title</label>
                        <input id="featured_images_leader_title" class="form-control" type="text" name="featured_images[leader][title]"
                               value="{{ old('featured_images.leader.title') ?? ($settings['featured_images']['leader']['title'] ?? '') }}" required/>
                    </div>
                </div>
                <div class='col-12 mt-2'>
                    <button type="submit"
                            class="btn btn-primary me-1 waves-effect waves-float waves-light">Save Settings
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
