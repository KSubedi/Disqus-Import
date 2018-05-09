<?php

class DisqusImportAdmin {
    // Registers admin view
    public static function registerAdminView(){
        // Set options here
        $page_title = 'Disqus Import';
        $menu_title = 'Disqus Import';
        $capability = 'manage_options';
        $menu_slug  = 'disqus-import';
        $function   = 'DisqusImportAdmin::renderView';
    
        // Add the page
        add_management_page( $page_title,
                    $menu_title, 
                    $capability, 
                    $menu_slug, 
                    $function);

    }

    // Renders the view
    public static function renderView(){
        // Process form data
        DisqusImportAdmin::processForm();

        // Echo the view
        echo "
            <h1>Disqus Import</h1>
            <hr/>
            <p>Upload the Disqus Export XML file below.</p>


            <form  method=\"post\" enctype=\"multipart/form-data\">
                    <input type='file' id='disqus_xml' name='disqus_xml'></input>
                    <br><br>
                    Regex For Links (Optional):
                    <input type='text' id='regex' name='regex'></input>

            ";

        // Submit button
        submit_button('Upload');
            
        echo '
            </form>
            <hr>
            <h5>Created by <a href="https://kaushalsubedi.com" target="_blank">Kaushal Subedi</a>.</h5>
        ';
    }

    // Process form here
    public static function processForm(){
        // Check if file has been posted
        if(isset($_FILES['disqus_xml'])){
            // Get the file
            $xmlFilePosted = $_FILES['disqus_xml'];

            // Upload media
            $uploaded=media_handle_upload('disqus_xml', 0);
            
            // Error checking using WP functions
            if(is_wp_error($uploaded)){
                echo "<div class='notice notice-error'>Error uploading file: " . $uploaded->get_error_message() . "</div>";
            }else{
                echo "<div class='notice notice-success'>File uploaded successfully!</div>";

                // Get the contents of xml and process it
                $xmlContents = file_get_contents(wp_get_attachment_url($uploaded));
                DisqusImportAdmin::processXml($xmlContents);
            }
        }
    }

    // Process XML
    public static function processXml($xmlContents){
        // Import thread reader
        require_once("thread-reader.php");

        $reader = new ThreadReader($xmlContents);

        // Stores disqus id mapping to post id
        $pages = array();

        // Loop through all threads in xml and map ids
        foreach($reader->read("_c.thread") as $thread){
            // Get the link of the post 
            $linkText = $reader->read("_c.link.0.text", $thread);

            // If regex is provided, match it
            if(isset($_POST['regex'])){
                if($_POST['regex'] != ""){
                    $matches = preg_match("#".$_POST['regex']."#", $linkText, $returns);
                    if($matches == 1){
                        $linkText = $returns[0];
                    }
                }
            }

            // Get the post id from url
            $postId = url_to_postid($linkText . "\n");

            // Add to array if its not 0
            if($postId != 0){
                $pages[$reader->read("--dsq:id", $thread)] = $postId;
            }
        }

        // Will hold the comment disqus id to wordpress id mapping
        $comments = array();

        // Counter for successfull comment additions
        $counter = 0;
        
        // Loop through all the posts
        foreach($reader->read("_c.post") as $post){
            // Get the disqus thread id
            $threadDsqId = $reader->read("_c.thread.0--dsq:id", $post);

            // Get the post disqus id
            $postDsqId = $reader->read("--dsq:id", $post);

            // If the parent post exists  
            if(array_key_exists($threadDsqId, $pages)){
                // Get the post id from array we created earlier
                $postId = $pages[$threadDsqId];

                // Get all the fields from XML
                $author = $reader->read("_c.author.0._c.name.0.text", $post);
                $authorEmail = $reader->read("_c.author.0._c.email.0.text", $post);
                $message = $reader->read("_c.message.0.text", $post);
                $time = $reader->read("_c.createdat.0.text", $post);
                $ip = $reader->read("_c.ipaddress.0.text", $post); 

                // Default parent id
                $parentId = 0;

                // Make sure the parent thread node exists on post
                if(array_key_exists("parent", $post["children"])){
                    // Get parent disqus id
                    $parentId = $reader->read("_c.parent.0--dsq:id", $post);
                    
                    // Assign the post id from disqus id
                    $parentId = $comments[$parentId];
                }
                
                // Fill the wp data
                $data = array(
                    'comment_post_ID' => $postId,
                    'comment_author' => $author,
                    'comment_author_email' => $authorEmail,
                    'comment_author_url' => '',
                    'comment_content' => $message,
                    'comment_type' => '',
                    'comment_parent' => $parentId,
                    'comment_author_IP' => $ip,
                    'comment_agent' => '',
                    'comment_date' => $time,
                    'comment_approved' => 1,
                );
 
                // Insert comment and get post id
                $commentId = wp_insert_comment($data, true);
                
                // If the comment id is not false, increase counter and map disqus id of comment to the comment id 
                if($commentId !== false){
                    $comments[$postDsqId] = $commentId;
                    $counter++;
                }else{
                    echo "<div class='notice notice-error'>Failed to insert comment " . $commentId . "</div>";
                }
            }

        }

        echo "<div class='notice notice-success'>Imported " . $counter . " comments!</div>";        

    }

}


?>


