<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Anime Exhibition - Post Section</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style/Post.css">

</head>
<body>

    <input type="radio" name="page-tab" id="tab-new-post" class="tab-switch">
    <input type="radio" name="page-tab" id="tab-community" class="tab-switch" checked> 
    <input type="radio" name="page-tab" id="tab-liked-posts-1" class="tab-switch">
    <input type="radio" name="page-tab" id="tab-liked-posts-2" class="tab-switch">

    <nav class="navbar">
        <a href="HomePage.html" class="navbar-brand">University <span>Anime</span> Exhibition</a>
        <div class="nav-links">
            <a href="HomePage.html">Home</a>
            <a href="Event.html">Event</a>
            <a href="Post.html" class="active">Post</a>
        </div>
        <div class="user-profile">
            <a href="Profile.html" class="user-profile-btn" title="Profile"><i class="fa-regular fa-circle-user"></i></a>
        </div>
    </nav>

    <div class="main-container">
        <div class="post-layout-grid">
            
            <div class="left-profile-column">
                <div class="post-card profile-container-card">
                    
                    <div class="profile-avatar-wrapper">
                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=150" alt="User Avatar">
                        <div class="status-badge"></div>
                    </div>
                    
                    <div class="profile-name">Akira_Kuro</div>
                    <div class="profile-bio">Computer Science Student & Anime Illustrator. Currently prepping character designs for the upcoming campus festival! 🌸</div>
                    
                    <div class="sidebar-menu">
                        <label class="menu-label" id="label-new-post" for="tab-new-post"><i class="fa-solid fa-square-plus"></i> New Post</label>
                        <label class="menu-label" id="label-community" for="tab-community"><i class="fa-solid fa-globe"></i> Community Post</label>
                    </div>

                    <div class="liked-posts-section">
                        <div class="section-mini-title"><i class="fa-solid fa-heart"></i> LIKED POSTS</div>
                        
                        <label class="liked-label-mini" id="label-liked-posts-1" for="tab-liked-posts-1">
                            <img class="liked-thumb" src="https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?q=80&w=100" alt="Thumb">
                            <div class="liked-info-text">
                                <div class="liked-title">Maid Assassin Illustration</div>
                                <div class="liked-author">By Maid_Assassin_Fan</div>
                            </div>
                        </label>

                        <label class="liked-label-mini" id="label-liked-posts-2" for="tab-liked-posts-2">
                            <img class="liked-thumb" src="https://images.unsplash.com/photo-1560942485-b2a11cc13456?q=80&w=100" alt="Thumb">
                            <div class="liked-info-text">
                                <div class="liked-title">Cosplay Grand Finals Info</div>
                                <div class="liked-author">By Admin_Exhib</div>
                            </div>
                        </label>
                        
                    </div>

                </div>
            </div>

            <div class="right-content-column">
                <div class="database-feed-container">
                    
                    <div class="panel-content" id="panel-new-post">
                        <div class="feed-item-card">
                            <div class="card-title"><i class="fa-solid fa-pen-to-square"></i> Share New Update</div>
                            <form onsubmit="event.preventDefault();">
                                <div class="form-group"><textarea class="form-control" placeholder="Write something here... What's on your mind?"></textarea></div>
                                <div class="form-group">
                                    <div class="image-upload-box"><i class="fa-regular fa-image"></i><p style="color: var(--text-light); margin-top: 8px;">Click to attach concept arts or cosplay sketches</p></div>
                                </div>
                                <button type="submit" class="btn-submit-post">Publish</button>
                            </form>
                        </div>
                    </div>



                    <div class="panel-content" id="panel-community">
                        <div class="feed-header-title">Community Square</div>
                        <div class="feed-header-subtitle">See what's happening around the exhibition.</div>
                        
                        <div class="feed-item-card">
                            <div class="feed-item-inner-grid">
                                <div class="feed-left-img-box"><img src="https://images.unsplash.com/photo-1560942485-b2a11cc13456?q=80&w=400"></div>
                                <div class="feed-right-info-box">
                                    <div class="feed-post-title">Cosplay Grand Championship Reg</div>
                                    <div class="feed-post-meta"><span>event date: 2026-06-16</span> <span>event time: 14:00</span></div>
                                    <div class="feed-post-description-box">Sign-ups for the university cosplay championship close this Friday. Make sure to upload your audio tracks!</div>
                                    <div class="feed-action-bar">
                                        <button class="action-btn"><i class="fa-regular fa-heart"></i></button>
                                        <button class="action-btn"><i class="fa-regular fa-comment"></i></button>
                                        <button class="action-btn"><i class="fa-regular fa-share-from-square"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel-content" id="panel-liked-posts-flow">
                        <div class="feed-header-title">My Like Post</div>
                        <div class="feed-header-subtitle">show the number of Like posts (123)</div>
                        
                        <div class="feed-item-card">
                            <div class="feed-item-inner-grid">
                                <div class="feed-left-img-box">
                                    <img src="https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?q=80&w=400" alt="Attached Art">
                                </div>
                                <div class="feed-right-info-box">
                                    <div class="feed-post-title">title1</div> 
                                    <div class="feed-post-meta">
                                        <span>event date: 2026-05-25</span> 
                                        <span>event time: 13:00</span>       
                                    </div>
                                    
                                    <div class="feed-post-description-box">
                                        Description: Just finished drafting the illustration for the "Maid Assassin" character assignment! Let me know what you think of the color palette!
                                    </div>
                                    
                                    <div class="feed-action-bar">
                                        <button class="action-btn active-liked"><i class="fa-solid fa-heart"></i></button>
                                        <button class="action-btn"><i class="fa-regular fa-comment"></i></button>
                                        <button class="action-btn"><i class="fa-regular fa-share-from-square"></i></button>
                                    </div>
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