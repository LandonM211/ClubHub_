                    <div id="active-members-tab" class="tab-content active">
                        <!-- Search and Filter -->
                        <div class="card" style="margin-bottom: 1.5rem;">
                            <div class="card-body" style="padding: 1rem;">
                                <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                                    <div style="flex: 1; min-width: 250px;">
                                        <div class="search-input-wrapper">
                                            <i class="fas fa-search"></i>
                                            <input type="text" id="memberSearchInput" class="form-input" placeholder="Search members by name or email..." style="padding-left: 2.5rem;">
                                        </div>
                                    </div>
                                    <select id="roleFilterSelect" class="form-input" style="width: auto; min-width: 150px;">
                                        <option value="">All Roles</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Members List -->
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">
                                    <i class="fas fa-users"></i> Club Members
                                    <span id="memberCount" class="badge">0</span>
                                </h2>
                            </div>
                            <div id="membersList">
                                <div class="loading">Loading members...</div>
                            </div>
                        </div>
                    </div>