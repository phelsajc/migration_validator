<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="#" class="brand-link">
        <span class="brand-text font-weight-light">My Admin</span>
    </a>
    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column">
                <!-- <li class="nav-item">
                    <a href="{{ route('home') }}" class="nav-link {{ Request::is('home') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-home"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('readersfee') }}" class="nav-link {{ Request::is('readersfee') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-money-check-alt"></i>
                        <p>Readers Fee</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('episodes') }}" class="nav-link {{ Request::is('episodes') ? 'active' : '' }}">
                        <i class="nav-icon fab fa-codiepie"></i>
                        <p>Episodes</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('audit_logs') }}" class="nav-link {{ Request::is('audit_logs') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-sticky-note"></i>
                        <p>Audit Logs</p>
                    </a>
                </li> -->
                <li class="nav-item">
                    <a href="{{ route('oecb.show') }}" class="nav-link {{ Request::is('oecb*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-clipboard-list"></i>
                        <p>OECB</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
