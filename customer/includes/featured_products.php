<?php
// Get featured products
$stmt = $conn->query("
    SELECT m.*, c.name as category_name 
    FROM Menu m 
    LEFT JOIN Categories c ON m.category_id = c.category_id 
    WHERE m.is_featured = 1 
    AND m.is_available = 1 
    AND m.is_deleted = FALSE
    AND m.stock_quantity > 0
    ORDER BY m.name
");
$featured_products = $stmt->fetchAll();
?>

<!-- Featured Products Slider -->
<div class="featured-products mb-5">
    <h2 class="special-title">Today's Special</h2>
    <div class="featured-slider-container position-relative">
        <div class="featured-slider">
            <?php foreach ($featured_products as $item): ?>
                <div class="featured-slide">
                    <div class="card h-100">
                        <div class="row g-0">
                            <div class="col-md-6">
                                <img src="../<?php echo htmlspecialchars($item['image_url']); ?>" 
                                     class="featured-image" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     onerror="this.src='../assets/images/placeholder.jpg'">
                            </div>
                            <div class="col-md-6">
                                <div class="card-body d-flex flex-column h-100">
                                    <h3 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <span class="category-badge mb-3"><?php echo htmlspecialchars($item['category_name']); ?></span>
                                    <p class="card-text flex-grow-1"><?php echo htmlspecialchars($item['description']); ?></p>
                                    <div class="d-flex justify-content-between align-items-center mt-auto">
                                        <span class="h3 mb-0">Rs. <?php echo number_format($item['price'], 2); ?></span>
                                        <?php if ($item['stock_quantity'] > 0): ?>
                                            <button class="btn btn-primary btn-lg add-to-cart" data-id="<?php echo $item['menu_id']; ?>">
                                                <i class="fas fa-cart-plus"></i> Add to Cart
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-lg" disabled>
                                                <i class="fas fa-times"></i> Out of Stock
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="slider-control prev" aria-label="Previous slide">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button class="slider-control next" aria-label="Next slide">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap');

.special-title {
    font-family: 'Playfair Display', serif;
    font-size: 3.5rem;
    font-weight: 700;
    text-align: center;
    margin-bottom: 2rem;
    color: #2c3e50;
    text-transform: uppercase;
    letter-spacing: 2px;
    position: relative;
    padding-bottom: 1rem;
}

.special-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 100px;
    height: 3px;
    background: linear-gradient(to right, #ff6b6b, #ff8e8e);
    border-radius: 2px;
}

.category-badge {
    background-color: #e9ecef;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.9em;
    color: #495057;
    display: inline-block;
    white-space: nowrap;
    width: 30%;
    text-align: left;
    overflow: hidden;
    text-overflow: ellipsis;
}

.featured-slider-container {
    position: relative;
    padding: 0 60px;
    margin: 0 auto;
    max-width: 1200px;
    height: 500px; /* Fixed height for container */
}

.featured-slider {
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
}

.featured-slide {
    display: none;
    width: 100%;
    height: 100%;
}

.featured-slide.active {
    display: block;
}

.card {
    border: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
    height: 100%;
}

.card .row {
    height: 100%;
}

.featured-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 8px 0 0 8px;
    transition: opacity 0.3s ease-in-out;
}

.card-body {
    padding: 2rem;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.card-title {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.card-text {
    font-size: 1.1rem;
    color: #666;
    margin-bottom: 1.5rem;
    flex-grow: 1;
    overflow-y: auto;
}

.slider-control {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0, 0, 0, 0.5);
    color: white;
    border: none;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease-in-out;
    font-size: 1.2rem;
    opacity: 0.7;
    z-index: 10;
}

.slider-control:hover {
    opacity: 1;
    transform: translateY(-50%) scale(1.1);
}

.slider-control.prev {
    left: 0;
}

.slider-control.next {
    right: 0;
}

@media (max-width: 768px) {
    .featured-slider-container {
        height: 800px; /* Taller height for mobile to accommodate stacked layout */
    }
    
    .featured-slide .row {
        flex-direction: column;
    }
    
    .featured-image {
        height: 300px;
        border-radius: 8px 8px 0 0;
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    .card-title {
        font-size: 1.5rem;
    }
}

/* Add smooth transition for all interactive elements */
.slider-control,
.featured-slide,
.card {
    transition: all 0.3s ease-in-out;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const slider = document.querySelector('.featured-slider');
    const prevBtn = document.querySelector('.slider-control.prev');
    const nextBtn = document.querySelector('.slider-control.next');
    const slides = document.querySelectorAll('.featured-slide');
    
    if (slides.length === 0) return; // Exit if no slides
    
    let currentIndex = 0;
    let isAnimating = false;
    let autoSlideInterval;
    
    function startAutoSlide() {
        if (autoSlideInterval) clearInterval(autoSlideInterval);
        autoSlideInterval = setInterval(nextSlide, 5000);
    }
    
    function updateSlider() {
        if (isAnimating) return;
        isAnimating = true;
        
        // Hide all slides
        slides.forEach(slide => {
            slide.style.display = 'none';
        });
        
        // Show current slide
        slides[currentIndex].style.display = 'block';
        
        // Reset animation flag
        setTimeout(() => {
            isAnimating = false;
        }, 500);
    }
    
    function nextSlide() {
        if (isAnimating) return;
        currentIndex = (currentIndex + 1) % slides.length;
        updateSlider();
    }
    
    function prevSlide() {
        if (isAnimating) return;
        currentIndex = (currentIndex - 1 + slides.length) % slides.length;
        updateSlider();
    }
    
    // Initialize slider
    updateSlider();
    
    // Event listeners for manual controls
    prevBtn.addEventListener('click', () => {
        clearInterval(autoSlideInterval);
        prevSlide();
        startAutoSlide();
    });
    
    nextBtn.addEventListener('click', () => {
        clearInterval(autoSlideInterval);
        nextSlide();
        startAutoSlide();
    });
    
    // Pause auto-slide on hover
    slider.addEventListener('mouseenter', () => {
        clearInterval(autoSlideInterval);
    });
    
    slider.addEventListener('mouseleave', () => {
        startAutoSlide();
    });
    
    // Start auto-slide
    startAutoSlide();
});
</script>

<script>
// Add image loading handler
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('.featured-image');
    images.forEach(img => {
        if (img.complete) {
            img.classList.add('loaded');
        } else {
            img.addEventListener('load', function() {
                this.classList.add('loaded');
            });
        }
    });
});
</script> 