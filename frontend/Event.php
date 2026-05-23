<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Anime Exhibition - Events System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style/Event.css">
    
</head>
<body>

    <input type="radio" name="page-tab" id="tab-user-list" class="view-switch" checked> 
    <input type="radio" name="page-tab" id="tab-my-events" class="view-switch">
    <input type="radio" name="page-tab" id="tab-event-detail" class="view-switch">
    <input type="radio" name="page-tab" id="tab-join-form" class="view-switch">
    <input type="radio" name="page-tab" id="tab-admin-list" class="view-switch">
    <input type="radio" name="page-tab" id="tab-admin-detail" class="view-switch">
    <input type="radio" name="page-tab" id="tab-admin-form" class="view-switch">
    <input type="radio" name="page-tab" id="tab-liked-posts-flow" class="view-switch">

    <nav class="navbar">
        <a href="HomePage.html" class="navbar-brand">University <span>Anime</span> Exhibition</a>
        <div class="nav-links">
            <a href="HomePage.html">Home</a>
            <a href="Event.html" class="active">Event</a>
            <a href="Post.html">Post</a>
        </div>
        
        <div style="display: flex; align-items: center;">
            <p><label for="tab-admin-list" class="admin-hello-tag" style="cursor: pointer; font-size: 13px;"><i class="fa-solid fa-screwdriver-wrench"></i> Hello, admin!!</label></p>
            <a href="Profile.html" class="user-profile-btn" title="Profile"><i class="fa-regular fa-circle-user"></i></a>
        </div>
    </nav>

    <div class="main-container">
        <div class="event-layout-grid">
            
            <div class="left-sidebar-column">
                <div class="event-card-box profile-container-card">
                    
                    <div class="profile-avatar-wrapper">
                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=150" alt="User Avatar">
                        <div class="status-badge"></div>
                    </div>
                    
                    <div class="profile-name">Akira_Kuro</div>
                    <div class="profile-bio">Computer Science Student & Anime Illustrator.🌸</div>
                    
                    <div class="sidebar-menu">
                        <label class="menu-label" id="label-all-events" for="tab-user-list"><i class="fa-solid fa-globe"></i> All Events</label>
                        <label class="menu-label" id="label-my-events" for="tab-my-events"><i class="fa-solid fa-paper-plane"></i> My Registered Event</label>
                    </div>

                </div>
            </div>

            <div class="right-content-column">
                <div class="event-card-box" style="border: none; background: transparent; padding: 0; box-shadow: none;">
                    
                    <div class="panel-content" id="panel-user-list">
                        <div class="feed-header-title">Events</div>
                        <div class="feed-header-subtitle">Explore upcoming anime activities on campus.</div>
                        
                        <div class="event-item-card">
                            <div class="event-item-inner-grid">
                                <div class="event-left-img-box"><img src="https://images.unsplash.com/photo-1541534741688-6078c6bfb5c5?q=80&w=400"></div>
                                <div class="event-right-info-box">
                                    <div class="event-main-title">Cosplay Championship</div>
                                    <div class="event-meta-row"><span>event date: 2026-06-16</span><span>event time: 14:00 - 18:00</span></div>
                                    <div class="event-description-container">Description: Join the most anticipated student cosplay grand finale on campus.</div>
                                    <label class="btn-know-more-action" for="tab-event-detail">know more</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel-content" id="panel-my-events">
                        <div class="feed-header-title">My Registered Events</div>
                        <div class="feed-header-subtitle">Review the anime activities you have signed up for.</div>
                        
                        <div class="event-item-card" style="border-left: 4px solid var(--green-btn);">
                            <div class="event-item-inner-grid">
                                <div class="event-left-img-box"><img src="https://images.unsplash.com/photo-1541534741688-6078c6bfb5c5?q=80&w=400"></div>
                                <div class="event-right-info-box">
                                    <div class="event-main-title">Cosplay Championship</div>
                                    <div class="event-meta-row" style="color: var(--green-btn); font-weight: 700;">Status: Successfully Registered <i class="fa-solid fa-circle-check"></i></div>
                                    <div class="event-description-container">Your arrival time slot is secured. Please check-in at the Main Hall on time!</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel-content" id="panel-event-detail">
                        <div class="event-card-box detail-view-container">
                            <div class="feed-header-title" style="text-align: center; font-size: 28px;">Events Name</div>
                            <div class="detail-flex-hero">
                                <img class="detail-big-cover" src="https://images.unsplash.com/photo-1541534741688-6078c6bfb5c5?q=80&w=400">
                                <div class="detail-info-sheet">
                                    <div class="event-main-title">title: Grand Stage Showcase</div>
                                    <div style="margin: 10px 0;"><div class="event-description-container" style="max-width: 100%;">Description: Join the most anticipated student cosplay grand finale on campus. High-fidelity costume judgments, live anisong bands, and premium panels.</div></div>
                                    <div class="event-meta-row"><span><strong>Date:</strong> 2026-06-16</span><br><span><strong>Time:</strong> 14:00 - 18:00</span></div>
                                </div>
                            </div>
                            <div class="bottom-control-bar">
                                <label class="btn-footer btn-cancel" for="tab-user-list">Back</label> 
                                <label class="btn-footer btn-confirm" for="tab-join-form">Join Event</label> 
                            </div>
                        </div>
                    </div>

                    <div class="panel-content" id="panel-join-form">
                        <div class="event-card-box">
                            <div class="feed-header-title" style="font-size: 24px;"><i class="fa-regular fa-calendar-check"></i> Select Your Arrival Time</div>
                            <p class="feed-header-subtitle">Your choice must be strictly confined within the official event timeline boundaries.</p>
                            
                            <form onsubmit="event.preventDefault(); alert('Registration Success! Locked inside database table.');">
                                <div class="form-group-item">
                                    <label>Date</label>
                                    <input type="date" class="form-field" value="2026-06-16" min="2026-06-16" max="2026-06-16" required>
                                </div>
                                <div class="form-group-item">
                                    <label>Time Slot</label>
                                    <input type="time" class="form-field" min="14:00" max="18:00" value="14:00" required>
                                </div>
                                <div style="display: flex; gap: 15px; margin-top: 30px;">
                                    <label class="btn-footer btn-cancel" style="flex:1; cursor:pointer;" for="tab-event-detail">Back</label>
                                    <button type="submit" class="btn-footer btn-confirm" style="flex:2;">Confirm Entry</button>
                                </div>
                            </form>
                        </div>
                    </div>


                    <div class="panel-content" id="panel-admin-list">
                        <div class="header-action-row">
                            <div>
                                <div class="feed-header-title">Events Console</div>
                                <div class="feed-header-subtitle">Admin privileged backend list operations.</div>
                            </div>
                            <label class="btn-top-add" for="tab-admin-form"><i class="fa-solid fa-plus"></i> Add Events</label>
                        </div>
                        <div class="event-item-card">
                            <div class="event-item-inner-grid">
                                <div class="feed-left-img-box"><img src="https://images.unsplash.com/photo-1541534741688-6078c6bfb5c5?q=80&w=400"></div>
                                <div class="event-right-info-box">
                                    <div class="event-main-title">Event Name: Cosplay Championship</div>
                                    <div class="event-meta-row"><span>title: Grand Stage Showcase</span></div>
                                    <div class="event-description-container">Description: Join the most anticipated student cosplay grand finale on campus.</div>
                                    <label class="btn-know-more-box" for="tab-admin-detail">know more</label>
                                </div>
                            </div>
                            <div class="admin-crud-bar">
                                <label class="crud-icon-btn" for="tab-admin-form" style="cursor: pointer;"><i class="fa-regular fa-pen-to-square"></i> Edit</label>
                                <button class="crud-icon-btn" onclick="alert('Delete this event from DB?')"><i class="fa-regular fa-trash-can"></i> Delete</button>
                            </div>
                        </div>
                    </div>

                    <div class="panel-content" id="panel-admin-detail">
                        <div class="event-card-box detail-view-container">
                            <div class="feed-header-title" style="text-align: center;">Events Name</div>
                            <div class="detail-flex-hero">
                                <img class="detail-big-cover" src="https://images.unsplash.com/photo-1541534741688-6078c6bfb5c5?q=80&w=400">
                                <div class="detail-info-sheet">
                                    <div class="event-main-title">title: Grand Stage Showcase</div>
                                    <div class="event-description-container" style="max-width:100%;">Description: Active judge list updated inside management rows.</div>
                                </div>
                            </div>
                            <div class="participants-box">
                                <h3><i class="fa-solid fa-list-ol"></i> List of participants</h3>
                                <ul class="participants-list">
                                    <li>User_01 (Akira_Kuro) - Attending Date: 2026-06-16 | Time: 14:30</li>
                                    <li>Maid_Assassin_Fan - Attending Date: 2026-06-16 | Time: 15:00</li>
                                </ul>
                            </div>
                            <div style="text-align: center;"><label class="btn-footer btn-cancel" for="tab-admin-list" style="cursor:pointer;">Back</label></div>
                        </div>
                    </div>

                    <div class="panel-content" id="panel-admin-form">
                        <div class="event-card-box">
                            <label class="menu-label" for="tab-admin-list" style="display:inline-flex; width:auto; border:1px solid var(--border-color); padding: 6px 12px; margin-bottom: 20px;"><i class="fa-solid fa-chevron-left"></i> Back</label>
                            <h2 style="font-family: var(--font-title); text-align: center; font-size: 28px; margin-bottom: 20px;">Plan Your Event</h2>
                            <div class="form-layout-split">
                                <div class="form-left-uploader">
                                    <div class="big-dash-uploader"><i class="fa-regular fa-image"></i><p style="font-size:13px; font-weight:600; margin-top:8px;">Please upload a picture</p></div>
                                </div>
                                <div class="form-right-inputs">
                                    <form onsubmit="event.preventDefault(); alert('Event data synchronized to database successfully.');">
                                        <div class="form-group-item"><label>Event Name</label><input type="text" class="form-field" value="Cosplay Championship"></div>
                                        <div class="form-group-item"><label>Location</label><input type="text" class="form-field" value="Main Hall, East Campus"></div>
                                        <div class="form-group-item"><label>Date</label><input type="date" class="form-field" value="2026-06-16"></div>
                                        <div class="form-group-item"><label>Time</label><input type="time" class="form-field" value="14:00"></div>
                                        <button type="submit" class="btn-submit-action">Submit</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </div> 
            </div> 
            
        </div> 
    </div> 

</body>
</html>