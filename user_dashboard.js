 /* =========== TOGGLE NAVIGATION BAR ===========*/
    let navMenu = document.getElementById('navMenu');
    let toggleBtn = document.getElementById('toggleBtn');
    function myMenuFunction() {
        if(navMenu.className === 'nav-menu') {
            navMenu.className += ' responsive';
            toggleBtn.className = 'uil uil-multiply';
        } else {
            navMenu.className = 'nav-menu';
            toggleBtn.className = 'uil uil-bars';
        }
    }
    function closeMenu(){
        navMenu.className = 'navMenu';
    }

    /* =========== HIDE NAV BAR WHEN SCROLLED TO SECTIONS ===========*/
    let navLink = document.querySelectorAll('.nav-link');

    function hideNav(){
        navMenu.className = 'nav-menu';
        toggleBtn.className = 'uil uil-bars';
    }

    navLink.forEach(link => {
        link.addEventListener('click', hideNav);
    })

    /* =========== CHANGE HEADER ON SCROLL ===========*/
    window.addEventListener('scroll', headerShadow);
    window.onload = headerShadow();

    function headerShadow(){
        const navHeader = document.getElementById('header');
        if(document.body.scrollTop > 50 || document.documentElement.scrollTop > 50) {
            navHeader.style.boxShadow = '0 4px 10px #000000BB';
            navHeader.style.height = '70px';
            navHeader.style.lineHeight = '70px';
            navHeader.style.background = '#cfcfcf';
            navHeader.style.backdropFilter = 'blur(8px)';
        } else {
            navHeader.style.boxShadow = 'none';
            navHeader.style.height = '90px';
            navHeader.style.lineHeight = '90px';
            navHeader.style.background = '#fff';
            navHeader.style.backdropFilter = 'blur(0px)';
        }
    }

    /* =========== SCROLL REVEAL ANIMATION ===========*/
    const sr = ScrollReveal ({
        origin: 'top',
        distance: '75px',
        duration: 1650,
        reset: false
    })

    sr.reveal('.featured-name, .featured-text-info, .featured-text-btn, .social-icons, .project-box, .service-box, .top-header', {delay: 50});

    const srLeft = ScrollReveal ({
        origin: 'left',
        distance: '80px',
        duration: 1750,
        reset: false
    })

    srLeft.reveal('.about-info, .contact-info, .skills-title, .skills-box, .form, .profile-image', {delay: 60});

    /* =========== CHANGE ACTIVE LINK ===========*/
    const sections = document.querySelectorAll('section[id]');
    function scrollActive() {
        const scrollY = window.scrollY;
        sections.forEach(current => {
            const sectionHeight = current.offsetHeight,
            sectionTop = current.offsetTop - 100,
            sectionId = current.getAttribute('id');
            if (scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
                document.querySelector('.' + sectionId).classList.add('active-link');
            } else {
                document.querySelector('.' + sectionId).classList.remove('active-link');
            }
        })
    }
    window.addEventListener('load', scrollActive);
    window.addEventListener('scroll', scrollActive);

    /* =========== SCROLL TO FUNCTION ===========*/
    const easingFunctions = {
        easeInOutCubic: t => t < 0.5 ? 4 * t * t * t : (t - 1) * (2 * t - 2) * (2 * t - 2) + 1,
    }
    let currentEasingFunction = 'easeInOutCubic';

    function scrollToTarget(target, offset = 0, duration = 2000, easingType = currentEasingFunction) {
        if (window.scrollAnimation) {
            cancelAnimationFrame(window.scrollAnimation);
            window.scrollAnimation = null;
        }
        const targetPosition = typeof target === 'number' ? target : target.getBoundingClientRect().top + window.scrollY;
        const startPosition = window.scrollY;
        const distance = targetPosition - startPosition - offset;
        if (Math.abs(distance) < 3){
            window.scrollTo(0, targetPosition - offset);
            return;
        }
        const startTime = performance.now();
        const easingFunction = easingFunctions[easingType] || easingFunctions.easeInOutCubic;
        function scrollAnimation(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const easedProgress = easingFunction(progress);
            const scrollAmount = startPosition + distance * easedProgress;
            window.scrollTo(0, scrollAmount);
            if (progress < 1) {
                window.scrollAnimation = requestAnimationFrame(scrollAnimation);
            } else {
                window.scrollAnimation = null;
            }
        }
        window.scrollAnimation = requestAnimationFrame(scrollAnimation);
    }

    /* =========== SECTION SCROLL FUNCTIONS ===========*/
    function scrollToHome() { scrollToTarget(0, 0, 2000); }
    function scrollToAbout() { scrollToTarget(document.getElementById('about'), 0, 2000); }
    function scrollToServices() { scrollToTarget(document.getElementById('services'), 0, 2000); }
    function scrollToContact() { scrollToTarget(document.getElementById('contact'), 0, 2000); }

    /* =========== FORM MODAL =========== */
document.getElementById("submit-form").addEventListener("click", function(event) {
    event.preventDefault();  // Prevent direct form submit

    // Get form values
    let firstName = document.querySelector("input[name='first_name']").value;
    let lastName = document.querySelector("input[name='last_name']").value;
    let studentNumber = document.querySelector("input[name='student_number']").value;
    let section = document.querySelector("input[name='section']").value;
    let department = document.querySelector("select[name='department']").value;
    let lastSchoolYear = document.querySelector("select[name='last_school_year']").value;
    let lastSemester = document.querySelector("select[name='last_semester']").value;
    let notes = document.querySelector("textarea[name='notes']").value;
    let documents = Array.from(document.querySelectorAll("input[name='documents[]']:checked")).map(doc => doc.value);
    let fileInput = document.getElementById("attachment");

    // Populate modal fields
    document.getElementById("modal_first_name").textContent = firstName;
    document.getElementById("modal_last_name").textContent = lastName;
    document.getElementById("modal_student_number").textContent = studentNumber;
    document.getElementById("modal_section").textContent = section;
    document.getElementById("modal_department").textContent = department;
    document.getElementById("modal_last_school_year").textContent = lastSchoolYear;
    document.getElementById("modal_last_semester").textContent = lastSemester;
    document.getElementById("modal_notes").textContent = notes;

    // Show documents
    let modalDocs = document.getElementById("modal_documents");
    modalDocs.innerHTML = '';  
    documents.forEach(doc => {
        let li = document.createElement("li");
        li.textContent = doc;
        modalDocs.appendChild(li);
    });

    // Show uploaded file name
    if (fileInput && fileInput.files.length > 0) {
        document.getElementById("modal_file").textContent = fileInput.files[0].name;
    } else {
        document.getElementById("modal_file").textContent = "No file uploaded";
    }

    // Show modal
    document.getElementById("modal").style.display = "block";
});

// Close modal
document.querySelector(".close").addEventListener("click", function() {
    document.getElementById("modal").style.display = "none";
});

// Final confirm -> submit form
document.getElementById("final-submit").addEventListener("click", function() {
    document.querySelector("form").submit();
});

//Calendar Modal
    
    