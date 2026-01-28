@extends('layouts.contentLayoutMaster')

@section('title', 'Verify Student Progress')

@section('vendor-style')
    <!-- vendor css files -->
    <style>
    .json-tree-wrapper {
        background-color: #f8f9fa;
        padding: 1rem;
        border-radius: 0.357rem;
        font-family: monospace;
        font-size: 14px;
        max-height: 600px;
        overflow-y: auto;
    }
    .json-tree-wrapper ul {
        list-style: none;
        margin: 0;
        padding: 0 0 0 20px;
    }
    .json-tree-wrapper li {
        position: relative;
        padding: 2px 0;
    }
    .json-tree-wrapper .key {
        color: #7367f0;
        font-weight: bold;
    }
    .json-tree-wrapper .string { color: #28c76f; }
    .json-tree-wrapper .number { color: #ff9f43; }
    .json-tree-wrapper .boolean { color: #00cfe8; }
    .json-tree-wrapper .null { color: #ff4961; }
    .json-tree-wrapper .collapsible {
        cursor: pointer;
        user-select: none;
    }
    .json-tree-wrapper .collapsible::before {
        content: '-';
        position: absolute;
        left: -15px;
        width: 10px;
        text-align: center;
        color: #666;
    }
    .json-tree-wrapper .collapsible.collapsed::before {
        content: '+';
    }
    .json-tree-wrapper .collapsed ~ ul {
        display: none;
    }
    </style>
@endsection

@section('vendor-script')
    <!-- vendor files -->
    <script>
    // Simple JSON Tree implementation
    class JSONTree {
        constructor(targetElement) {
            this.targetElement = targetElement;
        }

        render(data) {
            this.targetElement.innerHTML = '';
            this.targetElement.appendChild(this.createTree(data));
            this.setupListeners();
        }

        createTree(data) {
            const ul = document.createElement('ul');
            
            if (Array.isArray(data)) {
                data.forEach((item, index) => {
                    const li = document.createElement('li');
                    if (typeof item === 'object' && item !== null) {
                        li.innerHTML = `<span class="collapsible">[${index}]:</span>`;
                        li.appendChild(this.createTree(item));
                    } else {
                        li.innerHTML = `[${index}]: <span class="${typeof item}">${this.formatValue(item)}</span>`;
                    }
                    ul.appendChild(li);
                });
            } else if (typeof data === 'object' && data !== null) {
                Object.entries(data).forEach(([key, value]) => {
                    const li = document.createElement('li');
                    if (typeof value === 'object' && value !== null) {
                        li.innerHTML = `<span class="collapsible"><span class="key">${key}</span>:</span>`;
                        li.appendChild(this.createTree(value));
                    } else {
                        li.innerHTML = `<span class="key">${key}</span>: <span class="${typeof value}">${this.formatValue(value)}</span>`;
                    }
                    ul.appendChild(li);
                });
            }
            
            return ul;
        }

        formatValue(value) {
            if (typeof value === 'string') return `"${value}"`;
            if (value === null) return 'null';
            return value;
        }

        setupListeners() {
            this.targetElement.querySelectorAll('.collapsible').forEach(el => {
                el.addEventListener('click', () => {
                    el.classList.toggle('collapsed');
                });
            });
        }

        expandAll() {
            this.targetElement.querySelectorAll('.collapsible.collapsed').forEach(el => {
                el.classList.remove('collapsed');
            });
        }

        collapseAll() {
            this.targetElement.querySelectorAll('.collapsible').forEach(el => {
                el.classList.add('collapsed');
            });
        }
    }
    </script>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Verify Student Course Progress</h4>
            </div>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-md-6 mb-1">
                        <label class="form-label" for="student_id">Student ID</label>
                        <input type="number" class="form-control" id="student_id" name="student_id" placeholder="Enter Student ID" value="{{ $user_id ?? '' }}">
                    </div>
                    <div class="col-md-6 mb-1">
                        <label class="form-label" for="course_id">Course ID</label>
                        <input type="number" class="form-control" id="course_id" name="course_id" placeholder="Enter Course ID" value="{{ $course_id ?? '' }}">
                    </div>
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-primary" id="verifyBtn">Verify Progress</button>
                    </div>
                </div>
                <div class="row">
                    <!-- Left Window: Course Progress Details -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Course Progress Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-end mb-1">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" id="expandAll">Expand All</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="collapseAll">Collapse All</button>
                                </div>
                                <div id="json-renderer" class="json-tree-wrapper">
                                    Enter student and course IDs above and click Verify Progress
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Window: Direct DB Query -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">Direct DB Query</h5>
                            </div>
                            <div class="card-body">
                                <form id="queryForm" class="mb-2">
                                    <div class="row">
                                        <div class="col-md-6 mb-1">
                                            <label class="form-label" for="type">Type</label>
                                            <select class="form-select" id="type" name="type">
                                                <option value="lesson">Lesson</option>
                                                <option value="topic">Topic</option>
                                                <option value="quiz">Quiz</option>
                                                <option value="quiz_attempt">Quiz Attempt</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-1">
                                            <label class="form-label" for="id">ID</label>
                                            <input type="number" class="form-control" id="id" name="id" placeholder="Enter ID">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary mt-1">Query</button>
                                </form>
                                <pre id="queryResult" class="language-json" style="max-height: 500px; overflow-y: auto;">
                                    <code>Enter type and ID to query</code>
                                </pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
    $(function() {
        // Function to format JSON for display
        function formatJSON(json) {
            return JSON.stringify(json, null, 2);
        }

        // Function to update progress details
        function updateProgressDetails(userId, courseId) {
            $.get(`/playground/verify-progress/${userId}/${courseId}`, function(response) {
                $('#progressDetails code').text(formatJSON(response));
                hljs.highlightElement(document.querySelector('#progressDetails code'));
            });
        }

        // Function to handle direct DB query
        $('#queryForm').on('submit', function(e) {
            e.preventDefault();
            const type = $('#type').val();
            const id = $('#id').val();
            const userId = $('#userId').val();
            const courseId = $('#courseId').val();

            $.get(`/playground/verify-progress/query`, {
                type: type,
                id: id,
                user_id: userId,
                course_id: courseId
            }, function(response) {
                $('#queryResult code').text(formatJSON(response));
                hljs.highlightElement(document.querySelector('#queryResult code'));
            });
        });

        let jsonTree = new JSONTree(document.getElementById('json-renderer'));

        // Verify progress function
        function verifyProgress() {
            const student_id = $('#student_id').val();
            const course_id = $('#course_id').val();

            if (!student_id || !course_id) {
                $('#json-renderer').text('Please enter both Student ID and Course ID');
                return;
            }

            // Update URL without reloading
            const url = new URL(window.location);
            url.pathname = `/playground/verify-progress/${student_id}/${course_id}/details`;
            window.history.pushState({}, '', url);

            // Show loading state
            $('#json-renderer').text('Loading...');

            // Fetch progress details
            $.get(`/playground/verify-progress/${student_id}/${course_id}/details`, function(response) {
                // Initialize JSON viewer
                jsonTree.render(response);
            }).fail(function(error) {
                $('#json-renderer').text('Error fetching progress details: ' + error.responseText);
            });
        }

        // Handle expand/collapse buttons
        $('#expandAll').on('click', function() {
            jsonTree.expandAll();
        });

        $('#collapseAll').on('click', function() {
            jsonTree.collapseAll();
        });

        // Handle verify button click
        $('#verifyBtn').on('click', verifyProgress);

        // Handle Enter key in input fields
        $('#student_id, #course_id').on('keypress', function(e) {
            if (e.which === 13) {
                verifyProgress();
            }
        });

        // Initialize with existing values if present
        if ($('#student_id').val() && $('#course_id').val()) {
            verifyProgress();
        }
    });
</script>
@endsection
