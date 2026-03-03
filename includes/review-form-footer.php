<?php
add_action('wp_footer', function() {
    if (!is_singular('ytrip_tour')) return;
    ?>
    <div class="ytrip-review-section" style="padding:40px 20px;background:#f8fafc;margin-top:40px;clear:both">
        <div class="ytrip-container" style="max-width:1200px;margin:0 auto">
            <h3 style="margin-bottom:20px;font-size:1.5rem">✍️ Write a Review</h3>
            <form style="background:#fff;padding:30px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.1)">
                <div style="margin-bottom:20px">
                    <label style="display:block;margin-bottom:8px;font-weight:600">⭐ Rating</label>
                    <select name="rating" style="width:100%;padding:12px;border:1px solid #ddd;border-radius:8px">
                        <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                        <option value="4">⭐⭐⭐⭐ Very Good</option>
                        <option value="3">⭐⭐⭐ Good</option>
                    </select>
                </div>
                <div style="margin-bottom:20px">
                    <label style="display:block;margin-bottom:8px;font-weight:600">💬 Your Review</label>
                    <textarea name="content" rows="5" style="width:100%;padding:12px;border:1px solid #ddd;border-radius:8px"></textarea>
                </div>
                <button type="submit" style="background:#0f4c81;color:#fff;padding:14px 32px;border:none;border-radius:8px">Submit Review</button>
            </form>
        </div>
    </div>
    <script>
    // Move before footer
    document.addEventListener('DOMContentLoaded', function() {
        var review = document.querySelector('.ytrip-review-section');
        var footer = document.querySelector('footer');
        if (review && footer) {
            footer.parentNode.insertBefore(review, footer);
        }
    });
    </script>
    <?php
});
