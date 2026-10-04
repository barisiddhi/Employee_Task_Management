</main> <!-- Closes .main-content or .container -->
    
    <?php if (isset($_SESSION['user_id'])): ?>
        </div> 
    <?php endif; ?>

    <footer class="footer">
        <p>&copy; <?= date('Y') ?> Employee Task Management</p>
    </footer>

   
</body>
</html>