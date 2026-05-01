// ====================== PARTIE 1 : FORMULAIRE D'INSCRIPTION ======================

const form = document.getElementById("registerForm");

const nameInput = document.getElementById("name");
const emailInput = document.getElementById("email");
const phoneInput = document.getElementById("phone");
const dobInput = document.getElementById("dob");
const planInputs = document.querySelectorAll("input[name='plan']");
const termsCheckbox = document.querySelector("input[type='checkbox']");

const nameError = document.getElementById("nameError");
const emailError = document.getElementById("emailError");
const phoneError = document.getElementById("phoneError");
const dobError = document.getElementById("dobError");
const planError = document.getElementById("planError");
const termsError = document.getElementById("termsError");

const nameRegex = /^[A-Za-z\s]{3,}$/;
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const phoneRegex = /^[0-9]{8,15}$/;


function validateName() {
    if (!nameRegex.test(nameInput.value.trim())) {
        nameError.textContent = "Name must be at least 3 letters";
        nameInput.style.border = "2px solid red";
        return false;
    }
    nameError.textContent = "";
    nameInput.style.border = "2px solid green";
    return true;
}

function validateEmail() {
    if (!emailRegex.test(emailInput.value.trim())) {
        emailError.textContent = "Invalid email format";
        emailInput.style.border = "2px solid red";
        return false;
    }
    emailError.textContent = "";
    emailInput.style.border = "2px solid green";
    return true;
}

function validatePhone() {
    if (!phoneRegex.test(phoneInput.value.trim())) {
        phoneError.textContent = "Phone must be 8-15 digits";
        phoneInput.style.border = "2px solid red";
        return false;
    }
    phoneError.textContent = "";
    phoneInput.style.border = "2px solid green";
    return true;
}

function validateDOB() {
    if (!dobInput.value) {
        dobError.textContent = "Date of birth is required";
        dobInput.style.border = "2px solid red";
        return false;
    }

    const dob = new Date(dobInput.value);
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const monthDiff = today.getMonth() - dob.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
        age--;
    }

    if (age < 16) {
        dobError.textContent = "You must be at least 16 years old";
        dobInput.style.border = "2px solid red";
        return false;
    }

    dobError.textContent = "";
    dobInput.style.border = "2px solid green";
    return true;
}

function validatePlan() {
    const selected = Array.from(planInputs).some(plan => plan.checked);
    if (!selected) {
        planError.textContent = "Please select a plan";
        return false;
    }
    planError.textContent = "";
    return true;
}

function validateTerms() {
    if (!termsCheckbox.checked) {
        termsError.textContent = "You must accept the terms and conditions";
        return false;
    }
    termsError.textContent = "";
    return true;
}


// ====================== PARTIE 2 : CLASSES - FILTRES & TRI ======================

const classesData = [
    { name: "Pilates Core",      trainer: "Sarah.J", day: "Monday",    time: "10:00 AM", duration: 45, level: "Beginner" },
    { name: "Kickboxing Fitness",trainer: "Maria.L", day: "Tuesday",   time: "6:00 PM",  duration: 50, level: "Intermediate" },
    { name: "Prenatal Yoga",     trainer: "Amel.D",  day: "Wednesday", time: "11:00 AM", duration: 40, level: "Beginner" },
    { name: "Aqua Fitness",      trainer: "Lamia.S", day: "Thursday",  time: "5:00 PM",  duration: 45, level: "Beginner" },
    { name: "Paddle Training",   trainer: "Sofia.M", day: "Friday",    time: "4:00 PM",  duration: 60, level: "Intermediate" },
    { name: "Strength Training", trainer: "Farah.B", day: "Saturday",  time: "10:00 AM", duration: 50, level: "Advanced" }
];

let currentSort = { column: null, direction: 1 };

function renderTable(data) {
    const tbody = document.getElementById("classes-tbody");
    if (!tbody) return;

    tbody.innerHTML = "";

    data.forEach(item => {
        const row = document.createElement("tr");
        row.innerHTML = `
            <td><a href="classes.html">${item.name}</a></td>
            <td>${item.trainer}</td>
            <td>${item.day}</td>
            <td>${item.time}</td>
            <td>${item.duration} min</td>
            <td><span class="difficulty ${item.level.toLowerCase()}">${item.level}</span></td>
        `;
        tbody.appendChild(row);
    });
}

function convertTimeToMinutes(timeStr) {
    const [time, period] = timeStr.split(' ');
    let [hours, minutes] = time.split(':').map(Number);
    if (period === 'PM' && hours !== 12) hours += 12;
    if (period === 'AM' && hours === 12) hours = 0;
    return hours * 60 + minutes;
}

function applyFiltersAndSort() {
    const dayFilter   = document.getElementById("day");
    const levelFilter = document.getElementById("level");
    const trainerFilter = document.getElementById("trainer");

    let filtered = [...classesData];

    if (dayFilter && dayFilter.value !== "All Days") {
        filtered = filtered.filter(item => item.day === dayFilter.value);
    }
    if (levelFilter && levelFilter.value !== "All Levels") {
        filtered = filtered.filter(item => item.level === levelFilter.value);
    }
    if (trainerFilter && trainerFilter.value !== "All Trainers") {
        filtered = filtered.filter(item => item.trainer === trainerFilter.value);
    }

    if (currentSort.column) {
        filtered.sort((a, b) => {
            let valA = a[currentSort.column];
            let valB = b[currentSort.column];

            if (currentSort.column === "time") {
                valA = convertTimeToMinutes(valA);
                valB = convertTimeToMinutes(valB);
            } else if (currentSort.column === "duration") {
                valA = a.duration;
                valB = b.duration;
            }

            if (valA < valB) return -1 * currentSort.direction;
            if (valA > valB) return 1 * currentSort.direction;
            return 0;
        });
    }

    renderTable(filtered);
}

function setupClasses() {
    const dayFilter     = document.getElementById("day");
    const levelFilter   = document.getElementById("level");
    const trainerFilter = document.getElementById("trainer");

    if (dayFilter)     dayFilter.addEventListener("change", applyFiltersAndSort);
    if (levelFilter)   levelFilter.addEventListener("change", applyFiltersAndSort);
    if (trainerFilter) trainerFilter.addEventListener("change", applyFiltersAndSort);

    // Remplir Trainer dynamiquement
    const trainerSelect = document.getElementById("trainer");
    if (trainerSelect) {
        const trainers = [...new Set(classesData.map(c => c.trainer))];
        trainerSelect.innerHTML = '<option>All Trainers</option>';
        trainers.forEach(tr => {
            const opt = document.createElement("option");
            opt.value = tr;
            opt.textContent = tr;
            trainerSelect.appendChild(opt);
        });
    }

    // Tri sur les headers
    const headers = document.querySelectorAll(".classes-table th");
    const columns = ["name", "trainer", "day", "time", "duration", "level"];

    headers.forEach((header, index) => {
        header.style.cursor = "pointer";
        header.addEventListener("click", () => {
            const col = columns[index];

            if (currentSort.column === col) {
                currentSort.direction *= -1;
            } else {
                currentSort.column = col;
                currentSort.direction = 1;
            }

            headers.forEach(h => h.textContent = h.textContent.replace(/ [▲▼]$/, ""));
            const arrow = currentSort.direction === 1 ? " ▲" : " ▼";
            header.textContent = header.textContent.trim() + arrow;

            applyFiltersAndSort();
        });
    });

    renderTable(classesData);
}


// ====================== PARTIE 3 : MEMBERSHIP PLANS & MINI CART ======================

const membershipPlans = {
    bronze: { name: "Bronze Plan", price: "3500 DZD / month", value: "bronze" },
    silver: { name: "Silver Plan", price: "7000 DZD / month", value: "silver" },
    gold:   { name: "Gold Plan",   price: "12000 DZD / month", value: "gold"  }
};

const REGISTER_PAGE_URL = "membership.php";

function selectPlan(planKey) {
    const plan = membershipPlans[planKey];
    sessionStorage.setItem("selectedPlan", JSON.stringify(plan));
    updateMiniCart();
    alert(`You have selected: ${plan.name}\nPrice: ${plan.price}`);
}

function updateMiniCart() {
    const miniCart = document.getElementById("mini-cart");
    if (!miniCart) return;

    const savedPlan = sessionStorage.getItem("selectedPlan");

    if (savedPlan) {
        const plan = JSON.parse(savedPlan);
        miniCart.innerHTML = `
            <p><strong>Selected Plan:</strong> ${plan.name}</p>
            <p><strong>Price:</strong> ${plan.price}</p>
            <button onclick="proceedToRegister()" class="proceed-btn">Proceed to Register</button>
            <button onclick="clearPlanSelection()" class="cancel-btn">Cancel</button>
        `;
        miniCart.style.display = "block";
    } else {
        miniCart.style.display = "none";
    }
}

function clearPlanSelection() {
    sessionStorage.removeItem("selectedPlan");
    updateMiniCart();
}

function proceedToRegister() {
    const savedPlan = sessionStorage.getItem("selectedPlan");
    if (!savedPlan) return;

    const currentPath = window.location.pathname;
    const isOnMembershipPage = currentPath.endsWith("membership.php");

    if (isOnMembershipPage) {
        // Déjà sur la page membership : juste cocher le radio et scroller
        const plan = JSON.parse(savedPlan);
        const radioButton = document.querySelector(`input[name="plan"][value="${plan.value}"]`);
        if (radioButton) radioButton.checked = true;

        const registerSection = document.getElementById("membership-register-section");
        if (registerSection) registerSection.scrollIntoView({ behavior: "smooth" });
    } else {
        // Depuis n'importe quelle autre page : marquer qu'on veut scroller, puis rediriger
        sessionStorage.setItem("scrollToForm", "1");
        window.location.href = "membership.php";
    }
}

function addSelectButtons() {
    const cards = document.querySelectorAll('.membership-card');

    cards.forEach(card => {
        if (card.querySelector('.select-plan-btn')) return;

        const btn = document.createElement('button');
        btn.textContent = "Select Plan";
        btn.className = "select-plan-btn";

        if (card.classList.contains('bronze-card')) {
            btn.addEventListener('click', () => selectPlan('bronze'));
        } else if (card.classList.contains('silver-card')) {
            btn.addEventListener('click', () => selectPlan('silver'));
        } else if (card.classList.contains('gold-card')) {
            btn.addEventListener('click', () => selectPlan('gold'));
        }

        card.appendChild(btn);
    });
}

function createMiniCart() {
    let miniCart = document.getElementById("mini-cart");

    if (!miniCart) {
        miniCart = document.createElement("div");
        miniCart.id = "mini-cart";
        miniCart.className = "mini-cart";
        document.body.appendChild(miniCart);
    }

    updateMiniCart();
}

function autoPreselectPlan() {
    const savedPlan = sessionStorage.getItem("selectedPlan");
    if (!savedPlan) return;

    // Cocher le radio correspondant
    const plan = JSON.parse(savedPlan);
    const radioButton = document.querySelector(`input[name="plan"][value="${plan.value}"]`);
    if (radioButton) radioButton.checked = true;

    // Scroller vers le form UNIQUEMENT si l'utilisateur a cliqué "Proceed to Register"
    const shouldScroll = sessionStorage.getItem("scrollToForm");
    if (shouldScroll) {
        sessionStorage.removeItem("scrollToForm"); // consommer le flag
        const registerSection = document.getElementById("membership-register-section");
        if (registerSection) {
            setTimeout(() => registerSection.scrollIntoView({ behavior: "smooth" }), 300);
        }
    }
}

function initMembership() {
    console.log("🚀 Membership Cart Feature Initialized");
    addSelectButtons();
    createMiniCart();
    autoPreselectPlan();
    updateMiniCart();
    console.log("✅ Membership feature is ready!");
}


// ====================== PARTIE 4 : TRAINERS PAGE - SEARCH + MODAL ======================

const trainersData = [
    {
        id: 1,
        name: "Sarah J.",
        specialty: "Pilates Core Trainer",
        experience: "6 years",
        image: "images/sarah.jpg",
        bio: "Sarah specializes in Pilates and core stability training. She helps members improve posture, flexibility, and muscle control through focused and balanced workouts.",
        schedule: "Monday & Wednesday - 10:00 AM | Friday - 11:00 AM"
    },
    {
        id: 2,
        name: "Maria L.",
        specialty: "Kickboxing Fitness Trainer",
        experience: "7 years",
        image: "images/maria.jpg",
        bio: "Maria leads high-energy kickboxing classes designed to improve endurance, strength, and self-confidence through powerful full-body workouts.",
        schedule: "Tuesday & Thursday - 6:00 PM"
    },
    {
        id: 3,
        name: "Amel D.",
        specialty: "Prenatal Yoga Trainer",
        experience: "5 years",
        image: "images/amel.jpg",
        bio: "Amel specializes in prenatal yoga and safe fitness programs for expecting mothers. Her classes focus on relaxation, flexibility, and healthy movement.",
        schedule: "Wednesday - 11:00 AM"
    },
    {
        id: 4,
        name: "Lamia S.",
        specialty: "Aqua Fitness Trainer",
        experience: "6 years",
        image: "images/lamia.jpg",
        bio: "Lamia leads aquatic fitness classes in the swimming pool, helping members build strength and endurance with low-impact water workouts.",
        schedule: "Thursday - 5:00 PM"
    },
    {
        id: 5,
        name: "Sofia M.",
        specialty: "Paddle Training Coach",
        experience: "4 years",
        image: "images/sofia.jpg",
        bio: "Sofia coaches paddle training sessions that improve coordination, reaction speed, and agility while keeping workouts fun and social.",
        schedule: "Friday - 4:00 PM"
    },
    {
        id: 6,
        name: "Farah B.",
        specialty: "Strength Training Coach",
        experience: "8 years",
        image: "images/farah (2).jpg",
        bio: "Farah specializes in strength and resistance training. She helps members build muscle, improve endurance, and reach their fitness goals safely.",
        schedule: "Saturday - 10:00 AM"
    }
];

function setupSearch() {
    const searchInput = document.getElementById("trainer-search");
    if (!searchInput) return;

    searchInput.addEventListener("input", function () {
        filterTrainers(this.value.toLowerCase().trim());
    });
}

function filterTrainers(searchTerm) {
    const articles = document.querySelectorAll('.trainers-grid article');
    let resultsFound = 0;

    articles.forEach((article, index) => {
        const trainer = trainersData[index];
        if (!trainer) return;

        const name      = trainer.name.toLowerCase();
        const specialty = trainer.specialty.toLowerCase();

        if (name.includes(searchTerm) || specialty.includes(searchTerm)) {
            article.style.display = "block";
            resultsFound++;
        } else {
            article.style.display = "none";
        }
    });

    let noResults = document.getElementById("no-results");
    if (!noResults) {
        noResults = document.createElement("p");
        noResults.id = "no-results";
        noResults.style.textAlign  = "center";
        noResults.style.fontSize   = "1.2em";
        noResults.style.color      = "#666";
        noResults.style.gridColumn = "1 / -1";
        document.querySelector('.trainers-grid').appendChild(noResults);
    }

    noResults.textContent = "No trainers found matching your search.";
    noResults.style.display = (resultsFound === 0 && searchTerm !== "") ? "block" : "none";
}

function createModal() {
    if (document.getElementById("trainer-modal")) return;

    const modalHTML = `
        <div id="trainer-modal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:2000; justify-content:center; align-items:center;">
            <div class="modal-content" style="background:#fff; border-radius:12px; padding:30px; max-width:500px; width:90%; position:relative; text-align:center;">
                <span class="close-btn" style="position:absolute; top:12px; right:18px; font-size:1.8em; cursor:pointer; line-height:1;">&times;</span>
                <img id="modal-image" src="" alt="" style="width:150px; height:150px; object-fit:cover; border-radius:50%; margin-bottom:15px;">
                <h2 id="modal-name" style="margin-bottom:5px;"></h2>
                <p id="modal-specialty" style="color:#61851b; font-weight:bold; margin-bottom:10px;"></p>
                <p><strong>Experience:</strong> <span id="modal-experience"></span></p>
                <p id="modal-bio" style="margin:10px 0; text-align:left;"></p>
                <p><strong>Class Schedule:</strong> <span id="modal-schedule"></span></p>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);

    const modal = document.getElementById("trainer-modal");

    modal.querySelector(".close-btn").addEventListener("click", () => {
        modal.style.display = "none";
    });

    modal.addEventListener("click", (e) => {
        if (e.target === modal) modal.style.display = "none";
    });

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") modal.style.display = "none";
    });
}

function openTrainerModal(trainer) {
    const modal = document.getElementById("trainer-modal");
    document.getElementById("modal-image").src       = trainer.image;
    document.getElementById("modal-image").alt       = trainer.name;
    document.getElementById("modal-name").textContent      = trainer.name;
    document.getElementById("modal-specialty").textContent = trainer.specialty;
    document.getElementById("modal-experience").textContent = trainer.experience;
    document.getElementById("modal-bio").textContent      = trainer.bio;
    document.getElementById("modal-schedule").textContent  = trainer.schedule;
    modal.style.display = "flex";
}

function makeCardsClickable() {
    const articles = document.querySelectorAll('.trainers-grid article');

    articles.forEach((article, index) => {
        article.style.cursor = "pointer";

        article.addEventListener("click", (e) => {
            if (e.target.closest('a')) return;
            const trainer = trainersData[index];
            if (trainer) openTrainerModal(trainer);
        });
    });
}

function initTrainers() {
    console.log("🚀 Trainers Page JavaScript Loaded");
    setupSearch();
    createModal();
    makeCardsClickable();
    console.log("✅ Search and Modal are ready!");
}


// ====================== PARTIE 5 : CONTACT FORM & FEEDBACK ======================

function initContactForm() {
    const contactForm = document.querySelector('.contact-form form');
    if (!contactForm) return;

    const nameField    = document.getElementById('name');
    const emailField   = document.getElementById('email');
    const subjectField = document.getElementById('subject');
    const messageField = document.getElementById('message');

    function validateName() {
        const value = nameField.value.trim();
        if (value.length < 2) {
            showInlineError(nameField, "Name must be at least 2 characters");
            return false;
        }
        clearInlineError(nameField);
        return true;
    }

    function validateEmail() {
        const value = emailField.value.trim();
        if (!emailRegex.test(value)) {
            showInlineError(emailField, "Please enter a valid email address");
            return false;
        }
        clearInlineError(emailField);
        return true;
    }

    function validateSubject() {
        const value = subjectField.value.trim();
        if (value.length < 5) {
            showInlineError(subjectField, "Subject must be at least 5 characters");
            return false;
        }
        clearInlineError(subjectField);
        return true;
    }

    function validateMessage() {
        const value = messageField.value.trim();
        if (value.length < 20) {
            showInlineError(messageField, "Message must be at least 20 characters");
            return false;
        }
        clearInlineError(messageField);
        return true;
    }

    function showInlineError(input, message) {
        clearInlineError(input);
        const errorSpan = document.createElement('span');
        errorSpan.className   = 'inline-error';
        errorSpan.textContent = message;
        errorSpan.style.color      = 'red';
        errorSpan.style.fontSize   = '0.9em';
        errorSpan.style.display    = 'block';
        errorSpan.style.marginTop  = '5px';
        input.parentNode.insertBefore(errorSpan, input.nextSibling);
        input.style.border = "2px solid red";
    }

    function clearInlineError(input) {
        const existingError = input.parentNode.querySelector('.inline-error');
        if (existingError) existingError.remove();
        input.style.border = "2px solid green";
    }

    function setupCharacterCounter() {
        const counter = document.createElement('small');
        counter.style.display   = 'block';
        counter.style.marginTop = '5px';
        counter.style.color     = '#666';
        messageField.parentNode.appendChild(counter);

        messageField.addEventListener('input', () => {
            const length = messageField.value.trim().length;
            counter.textContent = `${length} / 20 characters minimum`;
            counter.style.color = length >= 20 ? 'green' : '#666';
        });
    }

    function handleSubmit(e) {
    e.preventDefault();

    const isNameValid    = validateName();
    const isEmailValid   = validateEmail();
    const isSubjectValid = validateSubject();
    const isMessageValid = validateMessage();

    if (!isNameValid || !isEmailValid || !isSubjectValid || !isMessageValid) return;

    fetch('contact.php', {
    method: 'POST',
    body: new FormData(contactForm)
})
.then(() => {
    // Sauvegarder aussi dans localStorage
    let messages = JSON.parse(localStorage.getItem('contactMessages')) || [];
    messages.push({
        id:      Date.now(),
        date:    new Date().toLocaleString(),
        name:    nameField.value.trim(),
        email:   emailField.value.trim(),
        subject: subjectField.value.trim(),
        message: messageField.value.trim()
    });
    localStorage.setItem('contactMessages', JSON.stringify(messages));

    showSuccessToast();
    contactForm.reset();
    [nameField, emailField, subjectField, messageField].forEach(field => {
        field.style.border = "";
    });
});
}


    

    function showSuccessToast() {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: #4CAF50;
            color: white;
            padding: 16px 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            z-index: 3000;
            font-weight: bold;
            opacity: 0;
            transition: all 0.4s ease;
        `;
        toast.textContent = "Thank you! Your message has been sent successfully.";
        document.body.appendChild(toast);
        setTimeout(() => toast.style.opacity = "1", 10);
        setTimeout(() => {
            toast.style.opacity = "0";
            setTimeout(() => toast.remove(), 500);
        }, 4000);
    }

    nameField.addEventListener('blur', validateName);
    emailField.addEventListener('blur', validateEmail);
    subjectField.addEventListener('blur', validateSubject);
    messageField.addEventListener('blur', validateMessage);
    contactForm.addEventListener('submit', handleSubmit);
    setupCharacterCounter();
}


// ====================== INITIALISATION GÉNÉRALE ======================

function init() {
    console.log("🚀 Initialisation de toutes les fonctionnalités...");

    if (form) {
        nameInput.addEventListener("blur", validateName);
        emailInput.addEventListener("blur", validateEmail);
        phoneInput.addEventListener("blur", validatePhone);
        dobInput.addEventListener("blur", validateDOB);

        form.addEventListener("submit", function (e) {
            e.preventDefault();

            const isValid =
                validateName()  &&
                validateEmail() &&
                validatePhone() &&
                validateDOB()   &&
                validatePlan()  &&
                validateTerms();

            if (isValid) {
                alert("Registration successful!");
                form.reset();
            }
        });
        console.log("✅ Formulaire d'inscription initialisé");
    }

    setupClasses();
    console.log("✅ Tout est prêt ! Formulaire + Filtres Classes fonctionnent.");
}


// ====================== LANCEMENT ======================

document.addEventListener("DOMContentLoaded", () => {
    init();            // Partie 1 : Formulaire + Partie 2 : Classes

    const currentPage = window.location.pathname;
    if (currentPage.includes('membership')) {
        initMembership();  // Partie 3 : boutons Select Plan + mini-cart + autoPreselect
    } else {
        createMiniCart();  // Sur toutes les autres pages : afficher le mini-cart si un plan est en session
    }

    // Partie 4 : Trainers — uniquement sur la page trainers
    if (document.getElementById("trainer-search") || document.querySelector('.trainers-grid')) {
        initTrainers();
    }

    initContactForm(); // Partie 5 : Contact Form
});

