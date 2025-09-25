    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!---------- UNICONS ----------> 
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!---------- CSS ----------> 
    <link rel="stylesheet" href="index.css">
    <!---------- FAVICON ----------> 
    <link rel="icon" href="assets/profile.jpg" type="image/jpg">
    <!---------- TITLE ----------> 
    <title>OLFU Queueing System</title>
</head>
<body>
    <div class="container">
<!---------- HEADER ----------> 
    <nav id="header">
        <div class="nav-logo" href="#home" onclick="scrollToHome()">
            <p class="nav-name"><span>OLFU</span> Antipolo Registar</p>
        </div>
        <div class="nav-menu" id="navMenu">
            <ul class="nav_menu_list">
                <li class="nav_list">
                    <a class="nav-link active-link home" onclick="scrollToHome()">Home</a>
                </li>
                <li class="nav_list">
                    <a class="nav-link about" onclick="scrollToAbout()">About</a>
                </li>
                <li class="nav_list">
                    <a class="nav-link services" onclick="scrollToServices()">Services</a>
                </li>
                <li class="nav_list">
                    <a class="nav-link contact" onclick="scrollToContact()">Contact</a>
                </li>
            </ul>
        </div>
        <div class="nav-menu-btn">
            <i class="uil uil-bars" id="toggleBtn" onclick="myMenuFunction()"></i>
        </div>
    </nav>
<!---------- MAIN ----------> 
<main class="wrapper">
<!---------- LANDING PAGE ----------> 
<section class="landing-page" id="home">
    <div class="feature-text">
        <div class="featured-name">
            <p>Welcome to <span>Queueing Services!</span></p>
        </div>
        <div class="featured-text-info">
            <p>Skip the long lines and manage your transactions efficiently with our Web and Mobile-Based Queueing System. Secure your queue number remotely, get real-time updates, and receive notifications when it’s your turn.</p><br>
            <p>Designed for efficiency and convenience, this system reduces congestion, streamlines workflows, and enhances the student experience. Say hello to a faster, smarter, and hassle-free way to handle your registrar needs!</p>
        </div>
        <div class="featured-text-btn">
            <button class="btn blue-btn" onclick="window.location.href='user_loginregis.php';">Start Now</button>
        </div>
    </div>
    <div class="profile-image">
        <div class="image">
            <img src="assets/fatimalogo.jpg" alt="avatar">
        </div>
    </div>
    <div class="scroll-btn" onclick="scrollToAbout()">
        <i class="fa-solid fa-angle-down"></i>
    </div>
</section>
<!---------- ABOUT BOX ----------> 
<section class="section" id="about">
    <div class="top-header">
        <h1>About <span>Us!</span></h1>
        <span>Queueing Management System</span>
    </div>
    <div class="row">
        <div class="col">
            <div class="about-info">
                <h3>Introduction</h3>
                <p>This Web and Mobile-Based Queueing System streamlines the Registrar’s Office processes by allowing students to secure queue numbers remotely, track real-time updates, and receive notifications when their turn is near.</p><br>

                <p><b>To use this system:</b></p><br>
                <p>✅ Log in with your student credentials.</p>
                <p>✅ Select a service (e.g., document requests, enrollment verification).</p>
                <p>✅ Get a queue number and monitor your estimated wait time.</p>
                <p>✅ Receive notifications when it’s your turn.</p><br>

                <p>With this system, students and staff at Our Lady of Fatima University Antipolo Campus can enjoy a faster, more organized, and hassle-free queuing experience!</p>
            </div>
        </div>
        <div class="col skills">
            <h3 class="skills-title">Documents Available for Request</h3>
            <div class="col skills-section">
                <div class="skills-box frontend">
                    <div class="skills-header">
                    </div>
                    <div class="skills-list">
                        <span>Transcript of Records (TOR)</span>
                        <span>Certificate of Enrollment</span>
                        <span>Certificate of Graduation</span>
                        <span>Subjects Crediting Evaluation</span>
                        <span>Enrollment Assessment Form (EAF)</span>
                    </div>
                </div>
                <div class="skills-box">
                    <div class="skills-header">
                    </div>
                    <div class="skills-list">
                        <span>Good Moral Certificate</span>
                        <span>Diploma (Certified True Copy)</span>
                        <span>Authentication of Documents</span>
                        <span>Completion Form</span>
                        <span>Others...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!---------- SERVICES ----------> 
<section class="section" id="services">
    <div class="top-header">
        <h1>Services</h1>
        <span></span>
    </div>
    <div class="service-container">
        <div class="service-box">
            <i class="uil uil-briefcase-alt"></i>
            <h3>Document Requests</h3>
            <label>Request official student documents such as transcripts, certificates, and diplomas.</label>
        </div>
        <div class="service-box">
            <i class="uil uil-users-alt"></i>
            <h3>Enrollment Services</h3>
            <label>Assistance with registration, subject adjustments, and cross-enrollment processing.</label>
        </div>
        <div class="service-box">
            <i class="uil uil-award"></i>
            <h3>Student Records Processing</h3>
            <label>Manage student records, verify academic status, and update personal information.</label>
        </div>
        <div class="service-box">
            <i class="uil uil-graduation-cap"></i>
            <h3>Clearance & Graduation Processing</h3>
            <label>Apply for graduation, process student clearance, and schedule exit interviews.</label>
        </div>
    </div>
</section>
<!---------- CONTACT ----------> 
<section class="section" id="contact">
    <div class="top-header">
        <h1>Get in touch</h1>
        <span>Have other concerns? Let's connect.</span>
    </div>
    <div class="row">
        <div class="col contact-info">
            <h2>Find Us</h2>
            <p><b>Antipolo Online Concierge</b></p>
            <p>Meeting ID: 965 9850 1717</p>
            <p>Password: 557028</p>
            <div class="contact-social-icons">
                <a href="https://www.facebook.com/our.lady.of.fatima.university" class="icon"><i class='uil uil-facebook-f'></i></a>
                <a href="https://www.instagram.com/fatimauniversity/" class="icon"><i class='uil uil-instagram'></i></a>
                <a href="https://www.youtube.com/channel/UC1xRi6L2EBtkWvVdmkNHYEg" class="icon"><i class='uil uil-youtube'></i></a>
                <a href="https://www.linkedin.com/school/our-lady-of-fatima-university/" class="icon"><i class='uil uil-linkedin-alt'></i></a>
            </div>
        </div>
        <div class="col">
            <div class="form">
                <div class="form-inputs">
                    <input type="name" class="input-field" placeholder="Name">
                    <input type="email" class="input-field" placeholder="Email">
                </div>
                <div class="text-area">
                    <textarea placeholder="Message"></textarea>
                </div>
                <div class="form-button">
                    <button class="btn">Send<i class="uil uil-message"></i></button>
                </div>
            </div>
        </div>
    </div>
</section>
</main>
<!---------- FOOTER ----------> 
<footer>
    <div class="top-footer">
        <p>OLFU</p>
    </div>
    <div class="middle-footer">
        <ul class="footer-menu">
            <li class="footer_menu_list">
                <a onclick="scrollToHome()">Home</a>
                <a onclick="scrollToAbout()">About</a>
                <a onclick="scrollToServices()">Services</a>
                <a onclick="scrollToContact()">Contact</a>
            </li>
        </ul>
    </div>
    <div class="bottom-footer">
        <p>Copyright &copy; <a href="#home" style="text-decoration: none;">OLFU</a></p>
    </div>
</footer>
    </div>
<!---------- SCROLL REVEAL JS LINK ----------> 
<script src="https://unpkg.com/scrollreveal"></script>
<!---------- MAIN JS ----------> 
<script src="index.js"></script>
</body>
</html>