<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login</title>
        <style>
            /* 全局重置，防止出现意外的滚动条 */
            html, body {
                margin: 0;
                padding: 0;
                width: 100%;
                height: 100vh;
                background-color: #fafafa;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                overflow: hidden; 
            }

            /* 父容器 */
            .container {
                display: flex;
                width: 100%;
                height: 100%;
            }

            /* 1. 左侧欢迎区域（修改核心） */
            .left-section {
                flex: 1;             /* 自适应宽度：不再固定尺寸，会根据屏幕拉长或缩短自动伸缩 */
                background: url('img/background/background_LoginLeft.jpg') no-repeat center center; 
                background-size: cover;
                opacity: 0.75;
                
                display: flex;
                flex-direction: column;
                justify-content: flex-start; /* 靠上对齐 */
                align-items: flex-start;     /* 靠左对齐 */
                
                /* 控制整体文字盒子距离左边缘和上边缘的间距 */
                padding-top: 100px;   
                padding-left: 50px;  
                padding-right: 20px;         /* 右侧安全间距，防止文字挨右边太近 */
                box-sizing: border-box;
                
                color: white;
                text-shadow: 0px 4px 12px rgba(0, 0, 0, 0.5);
            }

            /* 文字包裹盒：负责控制换行时最宽长到哪里 */
            .welcome-text-box {
                /* 当屏幕很大时，限制文字最多占 80% 的宽度，防止单行拉得太长不好看 */
                max-width: 80%;      
                text-align: left;            /* 确保文字内部也是左对齐 */
            }

            .welcome-text-box h1 {
                margin: 0 0 40px 0;
                font-size: 3.2rem;
                font-weight: 700;
                
                /* 核心修改：允许自动换行，并确保在单词过长时可以在任意字符间断开换行 */
                white-space: normal;         
                word-break: break-word;      
            }
            
            .welcome-text-box h2 {
                margin: 0;
                font-size: 2.0rem;
                font-weight: 400;
                opacity: 0.9;
                
                /* 同理，允许副标题换行 */
                white-space: normal;
                word-break: break-word;
            }

            /* 2. 右侧表单区域（保持 Ins 风格的硬朗与稳定） */
            .right-section {
                width: 580px;                /* 右侧盒子固定宽度，不随浏览器缩放而改变尺寸 */
                flex-shrink: 0;              /* 绝对不允许被压缩 */
                background: white;
                background-size: cover;
                opacity: 0.75;

                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                padding: 40px;
                box-sizing: border-box;
                border-left: 1px solid #dbdbdb; 
            }

            /* 表单卡片框 */
            .login-card {
                width: 100%;
                max-width: 350px; 
                text-align: center;
            }

            .login-card h2 {
                font-size: 2rem;
                margin-bottom: 30px;
                color: #262626;
            }

            form {
                width: 100%;
            }
            .input-group {
                margin-bottom: 10px;
                text-align: left;
            }
            label {
                font-size: 14px;
                color: #1900ff;
                font-weight: bolder;
            }
            input[type="text"], input[type="password"] {
                width: 100%;
                padding: 12px;
                margin-top: 6px;
                background: #fafafa;
                border: 1px solid #dbdbdb;
                border-radius: 6px;
                box-sizing: border-box;
                font-size: 14px;
                outline: none;
            }
            input[type="text"]:focus, input[type="password"]:focus {
                border-color: #a8a8a8; 
            }
            input[type="submit"] {
                width: 100%;
                padding: 12px;
                margin-top: 15px;
                background-color: #0095f6; 
                color: white;
                border: none;
                border-radius: 8px;
                font-weight: 700;
                font-size: 14px;
                cursor: pointer;
            }
            input[type="submit"]:hover {
                background-color: #1877f2;
            }

            .login_logo {
                width: 100%;
                max-width: 320px;      
                height: auto;          
                margin-bottom: 15px;   
                display: inline-block;
                filter: drop-shadow(0px 4px 8px rgba(0, 0, 0, 0.1)); /* 淡淡的阴影，让Logo在浅色或星空底色上更立体 */
            }

            @media (max-width: 850px) {
                .left-section {
                    display: none; 
                }
                .right-section {
                    width: 100%; 
                    border-left: none;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            
            <div class="left-section">
                <div class="welcome-text-box">
                    <h1>University Anime Exhibition Website</h1>
                    <h2>Welcome to the Activities</h2>
                </div>
            </div>
            
            <div class="right-section">
                <div class="login-card">
                    <img src="img/Logo icon/Logo.png" class="login_logo" style="width: 150px; margin-bottom: 20px;">

                    <h2>Login</h2>
                    <form>
                        <div class="input-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        
                        <div class="input-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <p style="margin-top: 25px; text-align: left;"><a href="Register.html">If you don't have an account, click here</a></p>

                        <input type="submit" value="Login">
                    </form>
                </div>
            </div>

        </div>
    </body>
</html>