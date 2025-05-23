document.addEventListener("DOMContentLoaded", () => {
  // Import Bootstrap
  const bootstrap = window.bootstrap

  // Enable Bootstrap tooltips
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  var tooltipList = tooltipTriggerList.map((tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl))

  // Enable Bootstrap popovers
  var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
  var popoverList = popoverTriggerList.map((popoverTriggerEl) => new bootstrap.Popover(popoverTriggerEl))

  // Confirm delete actions
  document.querySelectorAll(".confirm-delete").forEach((element) => {
    element.addEventListener("click", (e) => {
      if (!confirm("Are you sure you want to delete this item? This action cannot be undone.")) {
        e.preventDefault()
      }
    })
  })

  // Toggle password visibility
  document.querySelectorAll(".toggle-password").forEach((element) => {
    element.addEventListener("click", function () {
      const passwordInput = document.querySelector(this.getAttribute("data-target"))
      const type = passwordInput.getAttribute("type") === "password" ? "text" : "password"
      passwordInput.setAttribute("type", type)
      this.querySelector("i").classList.toggle("bi-eye")
      this.querySelector("i").classList.toggle("bi-eye-slash")
    })
  })

  // Image preview for file inputs
  document.querySelectorAll(".image-upload").forEach((element) => {
    element.addEventListener("change", function () {
      const preview = document.querySelector(this.getAttribute("data-preview"))
      const file = this.files[0]

      if (file) {
        const reader = new FileReader()
        reader.addEventListener("load", () => {
          preview.src = reader.result
          preview.style.display = "block"
        })
        reader.readAsDataURL(file)
      }
    })
  })
})
