<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comment Section</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <section class="py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <form id="comment-form" class="mb-4">
                        <h2 class="text-center mb-4">Leave a Comment</h2>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="form-group fl_icon">
                                    <div class="icon"><i class="fa fa-user"></i></div>
                                    <input type="text" name="username" id="username" class="form-input"
                                        placeholder="Your Name" required>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group fl_icon">
                                    <div class="icon"><i class="fa fa-envelope"></i></div>
                                    <input type="email" name="email" id="email" class="form-input"
                                        placeholder="Your Email" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group fl_icon">
                                    <textarea name="comment" id="comment" class="form-input" rows="5"
                                        placeholder="Your Comment" required></textarea>
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

    <section class="comments-section py-5 bg-light">
        <div class="container">
            <div id="comments"></div>
            <div id="message-box" style="display: none; padding: 10px; margin-bottom: 15px; border-radius: 5px;"></div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>

</body>

</html>