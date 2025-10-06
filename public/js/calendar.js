// Compact Calendar JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Add click handlers for task bars
    document.querySelectorAll('.task-bar').forEach(bar => {
        bar.addEventListener('click', function() {
            alert('Task: ' + this.getAttribute('title'));
        });
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        const view = new URLSearchParams(window.location.search).get('view') || 'month';
        const date = new URLSearchParams(window.location.search).get('date') || '';
        
        switch(e.key) {
            case 'ArrowLeft':
                document.querySelector('.nav-btn').click();
                break;
            case 'ArrowRight':
                document.querySelectorAll('.nav-btn')[1].click();
                break;
            case 'w':
                location.href = `?view=week&date=${date}`;
                break;
            case 'm':
                location.href = `?view=month&date=${date}`;
                break;
            case 'y':
                location.href = `?view=year&date=${date}`;
                break;
            case 't':
                location.href = `?view=${view}`;
                break;
        }
    });
});