<?php
$pageStyles = ['form-main.css'];
include __DIR__ . '/includes/head.php';
?>

<body class="site-body">

<?php include __DIR__ . '/includes/header.php'; ?>

	<main class="form-main">
        <section class="form-page">
            <div class="site-title">
                <h1>PrismStar</h1>
                <p class="card-tagline">Shine in every color.</p>
            </div>
            <div>
                <h1 class="page-title">お問合せフォーム</h1>
            </div>

            <form id="contactForm" class="site-form">
                <div>
                    <div>
                        <span class="required-item">必須</span>
                        <label for="first-name">姓</label>
                        <input class="form-box" type="text" name="first-name" id="first-name"
                        placeholder="first name（必須）" required>
                    </div>
                    <div>
                        <span class="required-item">必須</span>
                        <label for="last-name">名</label>
                        <input class="form-box" type="text" name="last-name" id="last-name"
                        placeholder="last name（必須）" required>
                    </div>
                </div>

                <div>
                    <span class="required-item">必須</span>
                    <label for="mail">メールアドレス</label>
                    <input class="form-box"  type="email" name="mail" id="mail"
                    placeholder="mail address（必須）" required>
                </div>

                <div class="msg-form">
                    <span class="any-item">任意</span>
                    <label for="message">お問合せ内容</label>
                    <textarea class="msg-box"  name="message" placeholder="お問合せ内容を記載ください（任意）"></textarea>
                </div>
                <button type="submit">送信する</button>
            </form>
        </section>
	</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

  <script src="<?= htmlspecialchars(asset('Script/form.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
