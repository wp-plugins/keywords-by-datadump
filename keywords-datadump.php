<?php
/*
Plugin Name: Keywords by datadump
Plugin URI: http://datadump.ath.cx/wordpress/2008/04/meta-keywords-plugin-wordpress/
Description: Adds a sensible meta keywords tag to all your pages. The goal is to produce 6-7 short keyphrases (of one or two, or possibly three keywords each) and include them in a keywords meta tag in the document header. These keyphrases should be representative of the document's focus and content. N-gram (max n=2) analysis is used on the page content after stopword removal to build the keyword list. Dirty and simple, just the way I like it!
Author: Leon Derczynski
Version: 0.2
Author URI: http://datadump.ath.cx/
*/

function build_meta_keywords_tag() {
    define('KDD_KEYW_COUNT', 6);
    define('KDD_STOPWORD_FILE', 'keywords-datadump-cacm.txt');

    global $wp_query;

    $post   = $wp_query->post->post_content;
    $title  = $wp_query->post->post_title;

/*
   1. Concatenate post title, body text
   2. Strip tags
   3. Fold case
   4. Replace anything not in [a-z0-9]i with spaces
   5. Remove stopwords
   6. Boil down spaces
   7. Build n-gram list where n [is a member of] {1,2}
   8. Pick 7 most relevant n-grams
   9. Stick these together with commas
  10. Print the meta tag on the page
*/

    $rawtext = $title.' '.$post.' '.$title; //weight the title doubly. introduces mild bigram noise; not perfect.
    $rawtext = strip_tags($rawtext);
    $rawtext = strtolower($rawtext);
    $rawtext = preg_replace('|[^A-Za-z0-9\ ]|', ' ', $rawtext);

    $stopwords = explode("\n", trim(file_get_contents(preg_replace('|[\\\/][^\\\/]+?$|', '/', __FILE__).KDD_STOPWORD_FILE)));
    foreach ($stopwords as $stopword) {
        // painfully slow.
        $rawtext = preg_replace('|\b'.$stopword.'\b|', '', $rawtext);
    }

    $rawtext = preg_replace('|\s+|', ' ', $rawtext);

    // get unigrams
    $unigrams = explode(' ', $rawtext);

    // perform two-pass bigram matching; at PHP5.2.3, preg_match_all has this behaviour: "After the first match is found, the subsequent searches are continued on from end of the last match. "
    preg_match_all('|\b(\w+? \w+?)\b|', $rawtext, $matches);
    $bigrams1 = $matches[1];

    preg_match_all('| \b(\w+? \w+?)\b|', $rawtext, $matches);
    $bigrams2 = $matches[1];

    $bigrams = array_merge($bigrams1, $bigrams2);
    unset($bigrams1, $bigrams2, $matches);

    // build ngram array
    $ngrams = array_merge($unigrams, $bigrams);
    unset($unigrams, $bigrams);

    // count ngrams
    $ngram_counts = array_count_values($ngrams);
    arsort($ngram_counts);


    $keywords = array_slice($ngram_counts, 0, KDD_KEYW_COUNT, true);

    $keywords = implode(',', array_keys($keywords));

    echo '<meta name="keywords" content="'.$keywords.'" />'."\n";



}

add_action('wp_head', 'build_meta_keywords_tag');

/*  Copyright 2008  Leon Derczynski  (email : leondz@gmail.com)

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
*/

?>