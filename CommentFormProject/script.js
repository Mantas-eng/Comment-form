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
        });
});

function loadComments() {
    fetch('load_comments.php')
        .then((response) => response.text())
        .then((data) => {
            document.getElementById('comments').innerHTML = data;
        });
}

document.addEventListener('click', function (e) {
    if (e.target.classList.contains('reply-btn')) {
        const parentId = e.target.getAttribute('data-id');
        const replyFormHTML = `
                <div class="container-fluid  d-flex justify-content-center align-items-center">
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
  <button type="submit" class="btn btn-success" style="width: 100%; background-color: #4CAF50; color: white; padding: 15px; font-size: 18px; border: none; border-radius: 5px; cursor: pointer; transition: background-color 0.3s;">Submit Reply </button>
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
                }
            });
    }
});



loadComments();