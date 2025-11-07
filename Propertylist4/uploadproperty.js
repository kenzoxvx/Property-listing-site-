const formEl = document.querySelector('#property-form');

formEl.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const submitBtn = formEl.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Uploading...';

    // Hide previous alerts
    const successAlert = document.querySelector('.alert');
    const errorAlert = document.querySelector('.alert1');
    
    if (successAlert) successAlert.style.display = 'none';
    if (errorAlert) errorAlert.style.display = 'none';

    try {
        const formData = new FormData(formEl);
        
        // Client-side validation
        const requiredFields = ['title', "contact", 'property_type', 'property_status', 'price', 
                               'area', 'bedrooms', 'bathrooms', 'year', 'location', 'description'];
        
        let missingFields = [];
        requiredFields.forEach(field => {
            const value = formData.get(field);
            if (!value || value.toString().trim() === '') {
                missingFields.push(field);
            }
        });
        
        if (missingFields.length > 0) {
            throw new Error(`Please fill in all required fields: ${missingFields.join(', ')}`);
        }
        
        // Validate at least one image is selected
        const imageInput = formEl.querySelector('input[type="file"]');
        if (!imageInput || imageInput.files.length === 0) {
            throw new Error('Please select at least one image');
        }

        const response = await fetch('uploadproperty.php', {
            method: 'POST',
            body: formData
        });

        const text = await response.text();
        
        try {
            const data = JSON.parse(text);
            
            if (response.ok && data.success) {
                // Success case
                if (successAlert) {
                    successAlert.style.display = 'flex';
                    successAlert.textContent = data.message || 'Property uploaded successfully!';
                    successAlert.style.backgroundColor = '#d4edda';
                    successAlert.style.color = '#155724';
                }
                
                // Reset form
                formEl.reset();
                
                // Optional: Redirect to property page or show preview
                if (data.property_id) {
                    console.log('Property ID:', data.property_id);
                    // You can add redirect logic here:
                    // window.location.href = `property.php?id=${data.property_id}`;
                }
            } else {
                // Server returned error
                throw new Error(data.message || `Server error: ${response.status}`);
            }
        } catch (e) {
            // Failed to parse JSON
            console.error('Failed to parse server response:', text);
            throw new Error('Invalid server response. Please try again.');
        }
    } catch (error) {
        // Show error message
        if (errorAlert) {
            errorAlert.style.display = 'flex';
            errorAlert.textContent = error.message;
            errorAlert.style.backgroundColor = '#f8d7da';
            errorAlert.style.color = '#721c24';
        }
        console.error('Upload error:', error.message);
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalBtnText;
    }
});

// Optional: Add image preview functionality
function setupImagePreview() {
    const imageInput = document.querySelector('input[type="file"]');
    const previewContainer = document.querySelector('.image-preview');
    
    if (imageInput && previewContainer) {
        imageInput.addEventListener('change', function(e) {
            previewContainer.innerHTML = '';
            
            if (this.files && this.files.length > 0) {
                for (let i = 0; i < this.files.length; i++) {
                    const file = this.files[i];
                    if (file.type.match('image.*')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.style.maxWidth = '100px';
                            img.style.maxHeight = '100px';
                            img.style.margin = '5px';
                            previewContainer.appendChild(img);
                        }
                        reader.readAsDataURL(file);
                    }
                }
            }
        });
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    setupImagePreview();
});

// Helper function to show temporary messages
function showTemporaryMessage(element, message, duration = 5000) {
    if (element) {
        element.textContent = message;
        element.style.display = 'flex';
        
        setTimeout(() => {
            element.style.display = 'none';
        }, duration);
    }
}