/**
 * Hybrid City Selector: Wheel (Desktop) & Bottom Sheet Grid (Mobile)
 */

class CityWheel {
    constructor(options = {}) {
        this.cities = options.cities || [];
        this.currentCity = options.currentCity || '';
        this.basePath = options.basePath || '?city=';
        
        // Cek Mobile
        this.isMobile = window.innerWidth <= 768;

        // Config Pagination
        // Di Mobile kita tampilkan semua (scrolling), di Desktop dipaging
        this.pageSize = this.isMobile ? 100 : 8; 
        this.currentPage = 0;
        this.totalPages = Math.ceil(this.cities.length / this.pageSize);
        
        this.isActive = false;
        this.init();

        // Listen resize untuk update layout jika orientasi HP berubah
        window.addEventListener('resize', () => {
            const newIsMobile = window.innerWidth <= 768;
            if (this.isMobile !== newIsMobile) {
                this.isMobile = newIsMobile;
                this.pageSize = this.isMobile ? 100 : 8;
                this.createWheelStructure(); // Re-render ulang
            }
        });
    }

    init() {
        this.createWheelStructure();
        this.attachEventListeners();
    }

    createWheelStructure() {
        const oldContainer = document.querySelector('.city-wheel-container');
        if (oldContainer) oldContainer.remove();

        this.container = document.createElement('div');
        this.container.className = 'city-wheel-container';
        
        const wrapper = document.createElement('div');
        wrapper.className = 'wheel-wrapper';

        // --- DESKTOP ONLY ELEMENTS ---
        if (!this.isMobile) {
            const circle = document.createElement('div');
            circle.className = 'wheel-circle';
            wrapper.appendChild(circle);

            this.centerInfo = document.createElement('div');
            this.centerInfo.className = 'wheel-center';
            this.updateCenterInfo(this.currentCity);
            wrapper.appendChild(this.centerInfo);
        }

        // --- SHARED ELEMENT (Grid/Wheel Items) ---
        this.wheelSelector = document.createElement('div');
        this.wheelSelector.className = 'wheel-selector';
        wrapper.appendChild(this.wheelSelector);

        // --- DESKTOP NAVIGATION ---
        if (!this.isMobile && this.totalPages > 1) {
            this.prevBtn = document.createElement('button');
            this.prevBtn.className = 'wheel-nav-btn prev';
            this.prevBtn.innerHTML = '<i class="bi bi-chevron-left"></i>';
            this.prevBtn.onclick = (e) => { e.stopPropagation(); this.changePage(-1); };
            wrapper.appendChild(this.prevBtn);

            this.nextBtn = document.createElement('button');
            this.nextBtn.className = 'wheel-nav-btn next';
            this.nextBtn.innerHTML = '<i class="bi bi-chevron-right"></i>';
            this.nextBtn.onclick = (e) => { e.stopPropagation(); this.changePage(1); };
            wrapper.appendChild(this.nextBtn);

            this.pageIndicator = document.createElement('div');
            this.pageIndicator.className = 'wheel-page-indicator';
            wrapper.appendChild(this.pageIndicator);
        }

        if (!this.isMobile) {
            const instructions = document.createElement('div');
            instructions.className = 'wheel-instructions';
            instructions.innerHTML = `SELECT <span class="key-badge">CLICK</span> <br> CLOSE <span class="key-badge">ESC</span>`;
            this.container.appendChild(instructions);
        }

        // Close on Click Outside (Desktop & Mobile backdrop)
        this.container.addEventListener('click', (e) => {
            if (e.target === this.container) this.close();
        });

        this.container.appendChild(wrapper);
        document.body.appendChild(this.container);

        this.renderCurrentPage();
    }

    renderCurrentPage() {
        this.wheelSelector.innerHTML = '';
        const start = this.currentPage * this.pageSize;
        const end = start + this.pageSize;
        const pageCities = this.cities.slice(start, end);

        pageCities.forEach((city, index) => {
            const segment = this.createSegment(city, index, pageCities.length);
            this.wheelSelector.appendChild(segment);
        });

        if (!this.isMobile && this.totalPages > 1) {
            this.pageIndicator.innerText = `PAGE ${this.currentPage + 1} / ${this.totalPages}`;
        }
    }

    createSegment(city, index, totalOnPage) {
        const segment = document.createElement('div');
        segment.className = 'city-segment';
        segment.dataset.city = city.name; // Penting buat CSS Mobile (attr content)
        
        if (city.name === this.currentCity) {
            segment.classList.add('active');
        }

        const icon = document.createElement('i');
        const icons = ['bi-building', 'bi-geo-alt-fill', 'bi-houses-fill', 'bi-map-fill', 'bi-cloud-sun-fill'];
        const iconClass = icons[index % icons.length];
        icon.className = `bi ${iconClass} segment-icon`;
        segment.appendChild(icon);

        // --- LOGIC POSISI ---
        if (!this.isMobile) {
            // Desktop: Hitung Rotasi Lingkaran
            const radius = 165; 
            const startAngle = -90; 
            const step = 360 / totalOnPage; 
            const angle = startAngle + (step * index);
            const rad = angle * (Math.PI / 180);
            const x = Math.cos(rad) * radius;
            const y = Math.sin(rad) * radius;
            segment.style.transform = `translate(calc(-50% + ${x}px), calc(-50% + ${y}px))`;
        } else {
            // Mobile: Reset Style agar ikut Grid CSS
            segment.style.transform = 'none';
            segment.style.position = 'relative';
            segment.style.left = 'auto';
            segment.style.top = 'auto';
        }

        // --- EVENTS ---
        if (!this.isMobile) {
            segment.addEventListener('mouseenter', () => {
                this.updateCenterInfo(city.name, true);
                this.highlightSegment(segment);
            });
        }

        segment.addEventListener('click', (e) => {
            e.stopPropagation();
            // Di Mobile: Langsung pilih (1x klik)
            // Di Desktop: Logic preview dulu
            if (this.isMobile || segment.classList.contains('active')) {
                this.selectCity(city.name);
            } else {
                this.updateCenterInfo(city.name, true);
                this.highlightSegment(segment);
            }
        });

        return segment;
    }

    changePage(direction) {
        let newPage = this.currentPage + direction;
        if (newPage < 0) newPage = this.totalPages - 1;
        if (newPage >= this.totalPages) newPage = 0;
        this.currentPage = newPage;
        
        this.wheelSelector.style.opacity = '0';
        setTimeout(() => {
            this.renderCurrentPage();
            this.wheelSelector.style.opacity = '1';
        }, 150);
    }

    updateCenterInfo(cityName, isHover = false) {
        if (!this.centerInfo) return;
        const stats = isHover ? 'SWITCH TO' : 'CURRENT LOC';
        const randomTemp = Math.floor(Math.random() * 5) + 28;
        this.centerInfo.innerHTML = `
            <div class="center-label">${stats}</div>
            <div class="center-city-name">${cityName}</div>
            <div class="center-stats">${randomTemp}°C</div>
        `;
    }

    highlightSegment(targetSeg) {
        const segments = this.wheelSelector.querySelectorAll('.city-segment');
        segments.forEach(seg => seg.classList.remove('active'));
        targetSeg.classList.add('active');
    }

    selectCity(cityName) {
        this.currentCity = cityName;
        // Efek loading simple
        document.body.style.cursor = 'wait';
        setTimeout(() => {
            this.close();
            window.location.href = this.basePath + encodeURIComponent(cityName);
        }, 150);
    }

    open() {
        this.isActive = true;
        this.container.classList.add('active');
        if (!this.isMobile) {
            // Logic halaman desktop
            const cityIndex = this.cities.findIndex(c => c.name === this.currentCity);
            this.currentPage = (cityIndex !== -1) ? Math.floor(cityIndex / this.pageSize) : 0;
            this.renderCurrentPage();
        }
    }

    close() {
        this.isActive = false;
        this.container.classList.remove('active');
        document.body.style.cursor = 'default';
    }

    attachEventListeners() {
        if (this._keydownListener) document.removeEventListener('keydown', this._keydownListener);
        this._keydownListener = (e) => {
            if (!this.isActive) return;
            if (e.key === 'Escape') this.close();
            if (!this.isMobile) {
                if (e.key === 'ArrowRight') this.changePage(1);
                if (e.key === 'ArrowLeft') this.changePage(-1);
            }
        };
        document.addEventListener('keydown', this._keydownListener);
    }
}

let wheelInstance = null;
function initCityWheel(citiesData, currentCity, basePath) {
    wheelInstance = new CityWheel({
        cities: citiesData,
        currentCity: currentCity,
        basePath: basePath
    });
}
function openCityWheel() {
    if (wheelInstance) wheelInstance.open();
    else alert("Menu kota sedang disiapkan...");
}