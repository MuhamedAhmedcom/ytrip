<?php
add_shortcode("ytrip_review_form", function() {
    return "<div style=\"padding:40px;background:#f8fafc;margin:40px 0\">
        <h3>Write a Review</h3>
        <form style=\"background:#fff;padding:30px;border-radius:12px\">
            <div style=\"margin-bottom:20px\">
                <label>Rating</label>
                <select name=\"rating\" style=\"width:100%;padding:12px;border:1px solid #ddd;border-radius:8px\">
                    <option value=\"5\">Excellent</option>
                    <option value=\"4\">Very Good</option>
                </select>
            </div>
            <div style=\"margin-bottom:20px\">
                <label>Review</label>
                <textarea name=\"content\" rows=\"5\" style=\"width:100%;padding:12px;border:1px solid #ddd;border-radius:8px\"></textarea>
            </div>
            <button style=\"background:#0f4c81;color:#fff;padding:14px 32px;border:none;border-radius:8px\">Submit</button>
        </form>
    </div>";
});
add_filter("the_content", function($c) {
    if (is_singular("ytrip_tour")) $c .= do_shortcode("[ytrip_review_form]");
    return $c;
});
