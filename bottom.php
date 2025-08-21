        </main> <!-- End Main Content -->
    </div> <!-- End App Wrapper -->

    <!-- Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t z-40 flex justify-around">
        <a href="index.php" class="flex-1 text-center py-2 text-gray-600 hover:text-blue-500">
            <i class="fas fa-home text-xl"></i>
            <span class="block text-xs">Home</span>
        </a>
        <a href="cart.php" class="flex-1 text-center py-2 text-gray-600 hover:text-blue-500 relative">
            <i class="fas fa-shopping-cart text-xl"></i>
            <span class="block text-xs">Cart</span>
            <?php
                $cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
                if ($cart_count > 0) {
                    echo "<span class='absolute top-1 right-1/2 -mr-5 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center'>$cart_count</span>";
                }
            ?>
        </a>
        <a href="profile.php" class="flex-1 text-center py-2 text-gray-600 hover:text-blue-500">
            <i class="fas fa-user text-xl"></i>
            <span class="block text-xs">Profile</span>
        </a>
    </nav>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Disable context menu, text selection, and zoom
            document.addEventListener('contextmenu', event => event.preventDefault());
            document.body.style.webkitUserSelect = 'none'; /* Safari */
            document.body.style.msUserSelect = 'none';   /* IE 10+ */
            document.body.style.userSelect = 'none';     /* Standard */
            document.addEventListener('keydown', function (event) {
                if ((event.ctrlKey || event.metaKey) && (event.key === '+' || event.key === '-' || event.key === '0')) {
                    event.preventDefault();
                }
            });
            document.addEventListener('wheel', function (event) {
                if (event.ctrlKey) {
                    event.preventDefault();
                }
            }, { passive: false });

            // Sidebar toggle logic
            const menuBtn = document.getElementById('menu-btn');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            const toggleSidebar = () => {
                sidebar.classList.toggle('-translate-x-full');
                overlay.classList.toggle('hidden');
            };

            if (menuBtn) {
                menuBtn.addEventListener('click', toggleSidebar);
            }
            if (overlay) {
                overlay.addEventListener('click', toggleSidebar);
            }
            
            // Reusable AJAX function
            window.ajax = async function(url, options) {
                const loadingModal = document.getElementById('loading-modal');
                loadingModal.classList.remove('hidden');
                try {
                    const response = await fetch(url, options);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return await response.json();
                } catch (error) {
                    console.error('AJAX Error:', error);
                    alert('An error occurred. Please try again.');
                    return { success: false, message: 'Network or server error.' };
                } finally {
                    loadingModal.classList.add('hidden');
                }
            };
        });
    </script>
</body>
</html>