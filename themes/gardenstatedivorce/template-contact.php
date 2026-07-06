<?php
/**
 * Template Name: Contact
 */
?>


<?php get_header(); ?>

<!-- ============================== header ============================== -->
    <section class="contact-header">
      <span class="badge">Talk to us</span>
      <h1 class="contact-header__title">Get in touch</h1>
      <p class="contact-header__lede">We're a small team keeping this roster accurate and useful. Whether you have a question or you're an attorney on the list, we'd like to hear from you.</p>
    </section>

    <!-- ============================= email cta ============================ -->
    <div class="email-cta-wrap">
      <div class="email-cta">
        <div class="email-cta__label">Reach us at</div>
        <a class="email-cta__address" href="mailto:contact@gardenstatedivorce.com">contact@gardenstatedivorce.com</a>
        <div>
          <a class="btn btn--primary" href="mailto:contact@gardenstatedivorce.com">
            <svg class="icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m2 7 10 6 10-6"></path></svg>
            Email us
          </a>
        </div>
      </div>
    </div>

    <!-- ============================== reasons ============================= -->
    <section class="reasons-wrap">
      <div class="reasons">
        <div class="reason">
          <div class="reason__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
          </div>
          <div class="reason__title">Have a question?</div>
          <p class="reason__body">If you're not sure how to use the directory or want help understanding what a certification means, send us a note. We can't give legal advice, but we're happy to point you in the right direction.</p>
        </div>

        <div class="reason">
          <div class="reason__icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
          </div>
          <div class="reason__title">Are you an attorney on our list?</div>
          <p class="reason__body">If your listing needs an update &mdash; a new firm, contact details, or a recognition to add &mdash; email us and we'll make the correction. We want every profile to be accurate and current.</p>
        </div>
      </div>
    </section>

<?php get_footer(); ?>