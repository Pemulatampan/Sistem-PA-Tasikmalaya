<?php
// Deteksi apakah file ada di subfolder atau root
$is_subfolder = (strpos($_SERVER['PHP_SELF'], '/pihak/') !== false);
$base_path = $is_subfolder ? '../' : '';
?>
        </div>
        <!-- End Content -->
    </div>
    <!-- End Main Content -->

    <!-- Global JavaScript -->
    <script src="<?php echo $base_path; ?>assets/js/global.js"></script>

    <!-- Page Specific JS -->
    <?php if (isset($additional_js)) echo $additional_js; ?>
</body>
</html>