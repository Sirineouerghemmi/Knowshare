document.addEventListener('DOMContentLoaded', function() {
    let list = document.querySelectorAll('.navigation li');
    
    function activeLink() {
        list.forEach(item => {
            item.classList.remove("hovered");
        });
        this.classList.add("hovered");
    }
    
    list.forEach(item => item.addEventListener("mouseover", activeLink));

    let toggle = document.querySelector(".toggle");
    let navigation = document.querySelector(".navigation");
    let main = document.querySelector(".main");
    
    toggle.onclick = function() {
        navigation.classList.toggle("active");
        main.classList.toggle("active");
    };

    // Navigation functionality
    const navLinks = document.querySelectorAll('.navigation a');
    const contentSections = document.querySelectorAll('.content-section');

    function activateSection(sectionId) {
        // Hide all sections
        contentSections.forEach(section => {
            section.style.display = 'none';
        });

        const activeSection = document.querySelector(`.${sectionId}-section`);
        if (activeSection) {
            activeSection.style.display = 'block';
        }

        navLinks.forEach(link => {
            link.parentElement.classList.remove('active');
            link.parentElement.classList.remove('hovered'); 
        });
        
        const activeLink = document.querySelector(`a[href="#${sectionId}"]`);
        if (activeLink) {
            activeLink.parentElement.classList.add('active');
        }
    }

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = this.getAttribute('href').substring(1); 
            
            if (target === 'logout') {
                console.log('Logging out...');
                return;
            }
            
            if (target) {
                activateSection(target);
                history.pushState(null, null, `#${target}`);
            }
        });
    });

    function handleInitialLoad() {
        const hash = window.location.hash.substring(1);
        if (hash) {
            activateSection(hash);
        } else {
            activateSection('dashboard');
        }
    }

    window.addEventListener('load', handleInitialLoad);
    window.addEventListener('popstate', handleInitialLoad);
}
);