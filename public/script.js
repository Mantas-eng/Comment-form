// Bendras funkcionalumas, naudojant delegavimą ir optimizuotą klaidų apdorojimą
document.addEventListener('submit', function (e) {
    e.preventDefault();

    // Tikriname, ar paspaustas mygtukas priklauso komentarų formoms
    const form = e.target;
    if (form.classList.contains('reply-form') || form.id === 'comment-form') {
        const formData = new FormData(form);
        const url = form.classList.contains('reply-form') ? 'save_comment.php' : 'save_comment.php';  // Galima keisti, jei atsakymų talpinimas įvyksta atskirai

        // Siunčiame užklausą į serverį
        fetch(url, {
            method: 'POST',
            body: formData,
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Nepavyko išsaugoti komentaro. Bandykite vėliau.');
                }
                return response.json();
            })
            .then((data) => {
                if (data.error) {
                    alert(data.error);
                } else {
                    alert(data.message);
                    loadComments(); // Atsinaujina komentarų sąrašas
                    if (form.classList.contains('reply-form')) {
                        form.closest('.reply-form').remove();  // Pašalina atsakymo formą
                        const parentId = form.getAttribute('data-parent-id');
                        enableReplyButton(parentId); // Leidžiame vėl atsakyti
                    }
                    form.reset(); // Išvalo formą
                    restoreCommentForm(); // Grąžina pagrindinę formą
                }
            })
            .catch((error) => {
                console.error('Error:', error);
                alert(error.message); // Informacija apie klaidą
            });
    }
});

// Panaudoja formą ir užpildo ją, kad galėtumėte vėl pridėti komentarą
function restoreCommentForm() {
    if (!document.getElementById('comment-form')) {
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
    }
}

// Užklausų duomenų užpildymas ir atsakymų klausimai
function enableReplyButton(parentId) {
    const parentComment = document.getElementById(`comment-${parentId}`);
    const replyBtn = parentComment.querySelector('.reply-btn');
    replyBtn.disabled = false; // Leidžia vėl atsakyti
}

// Pagrindinė funkcija, skirta įkelti komentarus
function loadComments() {
    fetch('load_comments.php')
        .then((response) => response.text())
        .then((data) => {
            document.getElementById('comments').innerHTML = data;
        })
        .catch((error) => console.error('Error loading comments:', error));
}

// Įkeliame komentarus kai tik puslapis įkraunamas
document.addEventListener('DOMContentLoaded', function() {
    loadComments();
});
