document.addEventListener('DOMContentLoaded', function() {
    // Mentor Search functionality
    const mentorSearch = document.getElementById('mentorSearch');
    if (mentorSearch) {
        mentorSearch.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            const items = document.querySelectorAll('.doubt-item-container');
            
            items.forEach(item => {
                const info = item.querySelector('.doubt-info');
                const studentNameSubject = info.querySelector('h3').textContent.toLowerCase();
                const questionText = info.querySelector('p').textContent.toLowerCase();
                // Student info in mentor view might contain phone
                const doubtFooter = item.querySelector('.doubt-footer') ? item.querySelector('.doubt-footer').textContent.toLowerCase() : '';
                
                if (studentNameSubject.includes(term) || questionText.includes(term) || doubtFooter.includes(term)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    // Download Records functionality
    const downloadBtn = document.getElementById('downloadRecords');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function() {
            const items = document.querySelectorAll('.doubt-item-container');
            let csv = 'Subject,Student,Question,Status,Date\n';
            
            items.forEach(item => {
                if (item.style.display === 'none') return;
                
                const info = item.querySelector('.doubt-info');
                const title = info.querySelector('h3').textContent;
                const question = info.querySelector('p').textContent.replace(/"/g, '""');
                const meta = item.querySelector('.doubt-meta');
                const status = meta.querySelector('.badge').textContent;
                const date = info.querySelectorAll('p')[1] ? info.querySelectorAll('p')[1].textContent : '';
                
                csv += `"${title.replace(/"/g, '""')}","","${question}","${status}","${date}"\n`;
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.setAttribute('hidden', '');
            a.setAttribute('href', url);
            a.setAttribute('download', 'doubt_records.csv');
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        });
    }

    const chatMessages = document.getElementById('chatMessages');
    const chatInput = document.getElementById('chatInput');
    const chatForm = document.getElementById('chatForm');

    // Mobile Nav Toggle with Overlay
    const navToggle = document.getElementById('navToggle');
    const navLinks = document.getElementById('navLinks');
    const navOverlay = document.getElementById('navOverlay');

    if (navToggle && navLinks && navOverlay) {
        // Remove existing listeners to avoid duplicates if script runs twice
        const newNavToggle = navToggle.cloneNode(true);
        navToggle.parentNode.replaceChild(newNavToggle, navToggle);
        const newNavOverlay = navOverlay.cloneNode(true);
        navOverlay.parentNode.replaceChild(newNavOverlay, navOverlay);

        const toggleMenu = () => {
            newNavToggle.classList.toggle('active');
            navLinks.classList.toggle('active');
            newNavOverlay.classList.toggle('active');
            document.body.style.overflow = navLinks.classList.contains('active') ? 'hidden' : '';
        };

        newNavToggle.addEventListener('click', e => {
            e.preventDefault();
            toggleMenu();
        });
        newNavOverlay.addEventListener('click', toggleMenu);

        // Close menu when clicking links
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                newNavToggle.classList.remove('active');
                navLinks.classList.remove('active');
                newNavOverlay.classList.remove('active');
                document.body.style.overflow = '';
            });
        });
    }

    if (!chatForm) return;

    function addMessage(text, type) {
        const msg = document.createElement('div');
        msg.className = 'chat-msg ' + type;
        msg.textContent = text;
        chatMessages.appendChild(msg);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    addMessage("Hi! I'm HeyyGuru Assistant. Ask me anything or type a greeting!", 'bot');

    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const text = chatInput.value.trim();
        if (!text) return;

        addMessage(text, 'user');
        chatInput.value = '';

        fetch('/chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: text, csrf_token: window.CSRF_TOKEN || '' })
        })
        .then(r => r.json())
        .then(data => {
            if (data.reply) {
                addMessage(data.reply, 'bot');
            }
            if (data.csrf_token) {
                window.CSRF_TOKEN = data.csrf_token;
            }
        })
        .catch(() => {
            addMessage("Sorry, something went wrong. Please try again.", 'bot');
        });
    });
});
