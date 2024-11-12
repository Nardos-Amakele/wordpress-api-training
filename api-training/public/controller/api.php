<?php
/**
 * Plugin Name: API Training Integration
 * Description: Integrates WordPress with a Flask backend for sentiment analysis.
 * Version: 1.0
 * Author: Khalid
 */

class Api_Training_APIs {
    public function __construct() {
        add_shortcode('my_shortcode', [$this, 'api_training_shortcodes']);
        add_action('admin_post_nopriv_submit_text', [$this, 'handle_form_submission']);
        add_action('admin_post_submit_text', [$this, 'handle_form_submission']);
    }

    function api_training_shortcodes() {
        ob_start();
        ?>
        <form id="textForm" method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="submit_text">
            <label for="userText">Enter text for analysis:</label>
            <input type="text" id="userText" name="userText" required>
            <button type="submit">Submit</button>
        </form>
        
        <div id="result">
            <?php if (isset($_GET['sentiment'])): ?>
                Sentiment: <?php echo esc_html($_GET['sentiment']); ?> (Confidence: <?php echo esc_html($_GET['confidence']); ?>)
            <?php elseif (isset($_GET['error'])): ?>
                Error: <?php echo esc_html($_GET['error']); ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    function handle_form_submission() {
        if (!isset($_POST['userText'])) {
            wp_redirect(add_query_arg('error', 'No text provided', wp_get_referer()));
            exit;
        }

        $userText = sanitize_text_field($_POST['userText']);
        $flaskUrl = 'http://192.168.51.11:5000/predict'; 

     
        $response = wp_remote_post($flaskUrl, [
            'method'    => 'POST',
            'headers'   => [
                'Content-Type' => 'application/json',
            ],
            'body'      => json_encode(['text' => $userText]),
        ]);

        if (is_wp_error($response)) {
            wp_redirect(add_query_arg('error', 'API request failed', wp_get_referer()));
            exit;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['sentiment'])) {
            wp_redirect(add_query_arg([
                'sentiment' => $data['sentiment'],
                'confidence' => number_format($data['confidence'], 2),
            ], wp_get_referer()));
        } else {
            wp_redirect(add_query_arg('error', 'Invalid response', wp_get_referer()));
        }
        exit;
    }
}

new Api_Training_APIs();
