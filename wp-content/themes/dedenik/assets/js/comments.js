// Start a top-level comment even when WordPress has moved the form into a thread.
document.getElementById('dedenik-add-comment')?.addEventListener('click', event => {
    if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    if (document.getElementById('wp-temp-form-div')) {
        document.getElementById('cancel-comment-reply-link')?.click();
    }
});
