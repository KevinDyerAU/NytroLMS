<li class="nav-item dropdown dropdown-notification me-25" id="NotificationDropdown">
    <a class="nav-link" href="javascript:void(0);" data-bs-toggle="dropdown">
        <i class="ficon" data-lucide="bell"></i>
        <span class="badge rounded-pill bg-danger badge-up" id="NotificationDropdown_Count" style="display: none;">0</span>
    </a>
    <ul class="dropdown-menu dropdown-menu-media dropdown-menu-end">
        <li class="dropdown-menu-header">
            <div class="dropdown-header d-flex">
                <h4 class="notification-title mb-0 me-auto">Notifications</h4>
                <div class="badge rounded-pill badge-light-primary" id="NotificationList_Count">0 New</div>
            </div>
        </li>
        <li class="scrollable-container media-list" id="NotificationList">
        </li>
        {{--              <li class="dropdown-menu-footer"> --}}
        {{--                  <a class="btn btn-primary w-100" href="javascript:void(0)">Read all notifications</a> --}}
        {{--              </li> --}}
    </ul>
</li>
