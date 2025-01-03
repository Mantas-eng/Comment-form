document.getElementById('comment-form').addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('https://pacific-springs-15861-df7e1201a420.herokuapp.com/save_comment.php', {
        method: 'POST',
        body: formData,
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.error) {
                alert(data.error);
            } else {
                alert(data.message);
                loadComments();
                this.reset();
            }
        })
        .catch(error => {
            alert('An error occurred: ' + error);
        });
});

function loadComments() {
    fetch('https://pacific-springs-15861-df7e1201a420.herokuapp.com/load_comments.php')
        .then((response) => response.json())
        .then((data) => {
            document.getElementById('comments').innerHTML = data;
        })
        .catch(error => {
            alert('Failed to load comments: ' + error);
        });
}

document.addEventListener('click', function (e) {
    if (e.target.classList.contains('reply-btn')) {
        const parentId = e.target.getAttribute('data-id');
        const replyFormHTML = `
            <div class="container-fluid d-flex justify-content-center align-items-center">
                <div class="p-4 bg-white border rounded shadow-sm">
                    <h5 class="mb-3 text-center">Reply to this comment</h5>
                    <form class="reply-form mt-3" data-parent-id="${parentId}">
                        <div class="row g-3">
                            <div class="col-md-6 fl_icon">
                                <div class="form-group">
                                    <div class="icon"><i class="fa fa-user"></i></div>
                                    <input type="text" class="form-input" name="username" placeholder="Your Name" required>
                                </div>
                            </div>
                            <div class="col-md-6 fl_icon">
                                <div class="form-group">
                                    <div class="icon"><i class="fa fa-envelope"></i></div>
                                    <input type="email" class="form-input" name="email" placeholder="Your Email" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group fl_icon">
                                    <textarea class="form-input" name="comment" rows="5" placeholder="Your Reply" required></textarea>
                                </div>
                            </div>
                            <input type="hidden" name="parent_id" value="${parentId}">
                            <div class="col-12">
                                <button type="submit" class="btn-submit">Submit Comment</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        `;

        const parentComment = document.getElementById(`comment-${parentId}`);
        parentComment.querySelector('.reply-form')?.remove(); 
        parentComment.insertAdjacentHTML('beforeend', replyFormHTML);
    }
});

document.addEventListener('submit', function (e) {
    if (e.target.classList.contains('reply-form')) {
        e.preventDefault();

        const formData = new FormData(e.target);

        fetch('save_comment.php', {
            method: 'POST',
            body: formData,
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.error) {
                    alert(data.error);
                } else {
                    alert(data.message);
                    loadComments();

                    e.target.closest('.reply-form').remove();
                }
            })
            .catch(error => {
                alert('An error occurred while submitting your reply: ' + error);
            });
    }
});

function addCommentFormSubmitListener() {
    document.getElementById('comment-form').addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('save_comment.php', {
            method: 'POST',
            body: formData,
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.error) {
                    alert(data.error);
                } else {
                    alert(data.message);
                    loadComments();
                    this.reset();
                }
            })
            .catch(error => {
                alert('An error occurred: ' + error);
            });
    });
}

loadComments();
