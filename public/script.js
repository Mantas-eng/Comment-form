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
        });
});

function loadComments() {
    fetch('https://pacific-springs-15861-df7e1201a420.herokuapp.com/load_comments.php')
        .then((response) => response.text())
        .then((data) => {
            document.getElementById('comments').innerHTML = data;
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

        const commentForm = document.getElementById('comment-form');
        if (commentForm) {
            const commentSection = commentForm.closest('section');
            if (commentSection) {
                commentSection.remove(); 
            }
        }
    }
});

document.addEventListener('submit', function (e) {
    if (e.target.classList.contains('reply-form')) {
        e.preventDefault();

        const formData = new FormData(e.target);

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

                    e.target.closest('.reply-form').remove();

                    const commentFormHTML = `
                        <section class="py-5 bg-light">
                            <div class="container">
                                <div class="row justify-content-center">
                                    <div class="col-lg-8">
                                        <div class="contact-form p-4 shadow-sm bg-white rounded">
                                            <form id="comment-form" class="mb-4">
                                                <div class="row g-3">
                                                    <div class="col-12 col-md-6 fl_icon">
                                                        <div class="form-group">
                                                            <div class="icon"><i class="fa fa-user"></i></div>
                                                            <input type="text" name="username" id="username" class="form-input" placeholder="Your Name" required>
                                                        </div>
                                                    </div>

                                                    <div class="col-12 col-md-6 fl_icon">
                                                        <div class="form-group">
                                                            <div class="icon"><i class="fa fa-envelope"></i></div>
                                                            <input type="email" name="email" id="email" class="form-input" placeholder="Your Email" required>
                                                        </div>
                                                    </div>

                                                    <div class="col-12">
                                                        <div class="form-group fl_icon">
                                                            <textarea name="comment" id="comment" class="form-input" rows="5" placeholder="Your Comment" required></textarea>
                                                        </div>
                                                    </div>

                                                    <input type="hidden" name="parent_id" id="parent-id" value="0">

                                                    <div class="col-12">
                                                        <button class="btn-submit" type="submit">Submit Comment</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    `;
                    document.querySelector('.container').insertAdjacentHTML('afterbegin', commentFormHTML);
                    addCommentFormSubmitListener(); 
                }
            });
    }
});

function addCommentFormSubmitListener() {
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
            });
    });
}

loadComments();
