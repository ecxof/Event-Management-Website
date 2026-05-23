<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Anime Exhibition - My Profile</title>
    <link rel="stylesheet" href="style/Profile.css">
    
</head>
<body>
<input type="radio" name="profile-state" id="state-view" class="state-switch" checked> 
    <input type="radio" name="profile-state" id="state-sent-posts" class="state-switch"> 
    <input type="radio" name="profile-state" id="state-like-posts" class="state-switch"> 
    <input type="radio" name="profile-state" id="state-edit" class="state-switch"> 
    <input type="radio" name="profile-state" id="state-detail-sent" class="state-switch"> 
    <input type="radio" name="profile-state" id="state-detail-liked" class="state-switch"> 

    <nav class="navbar">
        <a href="HomePage.html" class="navbar-brand">University <span>Anime</span> Exhibition</a>
        <div class="nav-links">
            <a href="HomePage.html">Home</a>
            <a href="Event.html">Event</a>
            <a href="Post.html">Post</a>
        </div>
        <div class="user-profile">
            <a href="Profile.html" class="user-profile-btn" title="My Profile"><i class="fa-solid fa-circle-user"></i></a>
        </div>
    </nav>

    <div class="main-container">
        <div class="profile-layout-grid">
            
            <div class="left-sidebar-column">
                <div class="profile-card sidebar-profile-box">
                    
                    <div class="avatar-wrapper">
                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=150" alt="User Avatar">
                    </div>
                    
                    <div class="profile-name">Akira_Kuro</div>
                    <div class="profile-bio" style="margin-bottom: 25px;">UID: 2026104082</div>
                    
                    <div class="sidebar-menu">
                        <label class="menu-item-link" id="label-my-profile" for="state-view">
                            <i class="fa-regular fa-user"></i> My Profile
                        </label>
                        
                        <label class="menu-item-link" id="label-sent-posts" for="state-sent-posts">
                            <i class="fa-regular fa-paper-plane"></i> Sent Post
                        </label>

                        <label class="menu-item-link" id="label-like-posts" for="state-like-posts">
                            <i class="fa-regular fa-heart"></i> Like Post
                        </label>
                        
                        <div class="logout-wrapper">
                            <button type="button" class="menu-item-link btn-logout" onclick="document.getElementById('logoutModal').showModal()">
                                <i class="fa-solid fa-right-from-bracket"></i> Logout
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <div class="right-content-column">
                
                <div class="profile-card state-display-panel" id="panel-view-mode">
                    <div class="section-title"><i class="fa-regular fa-user"></i> My Profile</div>
                    <div class="info-display-group">
                        <div class="info-display-label">Username</div>
                        <div class="info-display-value">Akira_Kuro</div>
                    </div>
                    <div class="info-display-group">
                        <div class="info-display-label">Bio</div>
                        <div class="info-display-value bio-text">Computer Science Student & Anime Illustrator. Currently prepping character designs for the upcoming campus festival! 🌸</div>
                    </div>
                    <div class="form-row-grid" style="margin-bottom: 0;">
                        <div class="form-item-block info-display-group">
                            <div class="info-display-label">Email Address</div>
                            <div class="info-display-value">akira.kuro@university.edu</div>
                        </div>
                        <div class="form-item-block info-display-group">
                            <div class="info-display-label">Phone Number</div>
                            <div class="info-display-value">+60 12-345 6789</div>
                        </div>
                    </div>
                    <div class="action-buttons-row" style="margin-top: 25px;">
                        <label class="btn-profile-action btn-edit" for="state-edit"><i class="fa-regular fa-pen-to-square" style="margin-right: 8px;"></i> Edit</label>
                    </div>
                </div>

                <div class="profile-card state-display-panel" id="panel-edit-mode">
                    <div class="section-title"><i class="fa-regular fa-id-card"></i> Profile Details</div>
                    <form onsubmit="event.preventDefault(); alert('Changes saved!'); document.getElementById('state-view').checked = true;">
                        <div class="form-row-grid"><div class="form-item-block"><label>Username</label><input type="text" class="profile-input" value="Akira_Kuro" required></div></div>
                        <div class="form-row-grid"><div class="form-item-block"><label>Bio</label><textarea class="profile-input">Computer Science Student & Anime Illustrator. Currently prepping character designs for the upcoming campus festival! 🌸</textarea></div></div>
                        <div class="form-row-grid">
                            <div class="form-item-block"><label>Email Address</label><input type="email" class="profile-input" value="akira.kuro@university.edu" required></div>
                            <div class="form-item-block"><label>Phone Number</label><input type="text" class="profile-input" value="+60 12-345 6789"></div>
                        </div>
                        <div class="action-buttons-row" style="margin-top: 25px;">
                            <label class="btn-profile-action btn-back" for="state-view">Back</label>
                            <button type="button" class="btn-profile-action btn-cancel" onclick="document.getElementById('state-view').checked = true;">Cancel</button>
                            <button type="submit" class="btn-profile-action btn-save">Save Changes</button>
                        </div>
                    </form>
                </div>

                <div class="state-display-panel" id="panel-sent-posts">
                    <div class="feed-header-title">My Sent Post</div>
                    <div class="feed-header-subtitle">Review all posts you have shared with the exhibition community.</div>
                    
                    <div class="scrollable-feed-list">
                        <div class="feed-item-card">
                            <div class="feed-item-inner-grid">
                                <div class="feed-left-img-box">
                                    <img src="https://images.unsplash.com/photo-1578632767115-351597cf2477?q=80&w=400" alt="Post Image">
                                </div>
                                <div class="feed-right-info-box">
                                    <div class="feed-post-title">Exhibition Prep Status</div>
                                    <div class="feed-post-meta"><span><i class="fa-regular fa-clock"></i> Post Date: 2026-05-15</span></div>
                                    <div class="feed-post-description-box">
                                        Description: Our group booth setup is 80% complete! Local poster printing has started.
                                    </div>
                                    
                                    <div class="feed-metrics-bar">
                                        <div class="metric-item-badge badge-likes"><i class="fa-solid fa-heart" style="color: #ff4757;"></i> 142 Likes</div>
                                        <div class="metric-item-badge badge-comments"><i class="fa-solid fa-comment" style="color: #0095f6;"></i> 28 Comments</div>
                                    </div>
                                    
                                    <label class="btn-view-detail" for="state-detail-sent">View Detail</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="state-display-panel" id="panel-like-posts">
                    <div class="feed-header-title">My Like Post</div>
                    <div class="feed-header-subtitle">Review all creative gallery updates you have liked around the town.</div>
                    
                    <div class="scrollable-feed-list">
                        <div class="feed-item-card">
                            <div class="feed-item-inner-grid">
                                <div class="feed-left-img-box">
                                    <img src="https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?q=80&w=400" alt="Cover Image">
                                </div>
                                <div class="feed-right-info-box">
                                    <div class="feed-post-title">Maid Illustration</div>
                                    <div class="feed-post-meta"><span><i class="fa-regular fa-user"></i> Author: Maid_Assassin_Fan</span><span><i class="fa-solid fa-heart" style="color:#ff4757;"></i> Liked Date: 2026-05-23</span></div>
                                    <div class="feed-post-description-box">
                                        Description: Just finished editing the weapon rendering anchor points in Adobe Illustrator!
                                    </div>
                                    <div class="feed-action-bar">
                                        <button class="action-btn active-liked"><i class="fa-solid fa-heart" style="color: #ff4757;"></i></button>
                                        <button class="action-btn"><i class="fa-regular fa-comment"></i></button>
                                    </div>
                                    
                                    <label class="btn-view-detail" for="state-detail-liked">View Detail</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="state-display-panel" id="panel-detail-sent">
                    <div class="profile-card post-detail-view-box">
                        <div class="feed-header-title" style="text-align: center; font-size: 26px;">Sent Post Information</div>
                        <div class="post-detail-hero-flex">
                            <img class="post-detail-big-cover" src="https://images.unsplash.com/photo-1578632767115-351597cf2477?q=80&w=600">
                            <div class="post-detail-info-sheet">
                                <h2 style="font-size: 22px; color: var(--dark-bg);">Exhibition Prep Status</h2>
                                <p style="font-size: 13px; color: var(--text-light); margin-top: 4px;">Published by: <strong>You (Akira_Kuro)</strong></p>
                                <div class="feed-post-description-box" style="margin-top: 15px; min-height: 110px;">
                                    Full Text Content: Our group booth setup is 80% complete! Local poster printing has started for the character assets presentation. Make sure to visit our block!
                                </div>
                            </div>
                        </div>
                        <div style="border-top:1px solid #f1f5f9; padding-top:20px;">
                            <label class="btn-profile-action btn-cancel" style="cursor: pointer;" for="state-sent-posts">Back to My Posts</label>
                        </div>
                    </div>
                </div>

                <div class="state-display-panel" id="panel-detail-liked">
                    <div class="profile-card post-detail-view-box">
                        <div class="feed-header-title" style="text-align: center; font-size: 26px;">Liked Post Information</div>
                        <div class="post-detail-hero-flex">
                            <img class="post-detail-big-cover" src="https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?q=80&w=600">
                            <div class="post-detail-info-sheet">
                                <h2 style="font-size: 22px; color: var(--dark-bg);">Maid Illustration</h2>
                                <p style="font-size: 13px; color: var(--text-light); margin-top: 4px;">Published by: <strong>Maid_Assassin_Fan</strong></p>
                                <div class="feed-post-description-box" style="margin-top: 15px; min-height: 110px;">
                                    Full Text Content: Just finished editing the weapon rendering anchor points in Adobe Illustrator for our visual narrative design assignment character. Let me know what you think about the outline layers!
                                </div>
                            </div>
                        </div>
                        <div style="border-top:1px solid #f1f5f9; padding-top:20px;">
                            <label class="btn-profile-action btn-cancel" style="cursor: pointer;" for="state-like-posts">Back to Likes</label>
                        </div>
                    </div>
                </div>

            </div> </div> 
    </div> 

    <dialog id="logoutModal" class="logout-dialog">
        <div class="dialog-title"><i class="fa-solid fa-circle-question" style="color: #ff4757; margin-right: 6px;"></i> Logout Confirmation</div>
        <div class="dialog-msg">Are you sure you want to sign out?</div>
        <div class="dialog-buttons">
            <button type="button" class="btn-dialog btn-no" onclick="document.getElementById('logoutModal').close()">No</button>
            <a href="Login.html" class="btn-dialog btn-yes">Yes</a>
        </div>
    </dialog>

</body>
</html>