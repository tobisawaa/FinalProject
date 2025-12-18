(() => {
  const cityInput = document.getElementById('cityInput');
  const searchBtn = document.getElementById('searchBtn');
  const cityName = document.getElementById('cityName');
  const temp = document.getElementById('temp');
  const desc = document.getElementById('desc');
  const weatherIcon = document.getElementById('weatherIcon');
  const detailCards = document.getElementById('detailCards');
  const toggleBtn = document.getElementById('toggleDetails');
  const moreInfo = document.getElementById('moreInfo');
  const extra = document.getElementById('extra');
  const currentDate = document.getElementById('currentDate');
  const unitToggle = document.getElementById('unitToggle');

  let currentUnit = localStorage.getItem('tempUnit') || 'C';
  let currentWeatherData = null;
  let currentCity = '';

  function formatDate(d){
    return d.toLocaleString();
  }
  currentDate.textContent = formatDate(new Date());

  // Temperature conversion functions
  function celsiusToFahrenheit(c) {
    return (c * 9/5) + 32;
  }

  function displayTemp(celsius) {
    if (currentUnit === 'F') {
      return Math.round(celsiusToFahrenheit(celsius));
    }
    return Math.round(celsius);
  }

  function updateUnitButton() {
    unitToggle.textContent = `°${currentUnit}`;
    unitToggle.classList.toggle('active', currentUnit === 'F');
  }

  function setLoading(){
    cityName.textContent = 'Loading...';
    temp.textContent = '--°';
    desc.textContent = '';
    weatherIcon.textContent = '⏳';
  }

  function render(data){
    currentWeatherData = data;
    cityName.textContent = data.city || 'Unknown';
    const tempDisplay = (data.temp_c != null) ? displayTemp(data.temp_c) + '°' + currentUnit : '--°';
    temp.textContent = tempDisplay;
    desc.textContent = data.description || '-';
    weatherIcon.textContent = iconFor(data.description || '');

    const cards = detailCards ? detailCards.querySelectorAll('.card') : [];
    if(cards.length >= 4){
      cards[0].querySelector('.v').textContent = (data.feels_like != null) ? displayTemp(data.feels_like) + '°' + currentUnit : '--°';
      cards[1].querySelector('.v').textContent = (data.humidity != null) ? data.humidity + '%' : '--%';
      cards[2].querySelector('.v').textContent = (data.wind_speed != null) ? Math.round(data.wind_speed * 3.6) + ' km/h' : '-- km/h';
      cards[3].querySelector('.v').textContent = (data.visibility != null) ? Math.round(data.visibility) + ' km' : '-- km';
    }

    const countryEl = document.getElementById('countryName');
    if(countryEl && data.country) countryEl.textContent = data.country;
    const feelsEl = document.getElementById('feelsLike');
    if(feelsEl) feelsEl.textContent = (data.feels_like != null) ? displayTemp(data.feels_like) + '°' + currentUnit : '--°';
  }

  function iconFor(desc){
    const d = desc.toLowerCase();
    if(d.includes('rain')) return '🌧️';
    if(d.includes('cloud')) return '☁️';
    if(d.includes('clear') || d.includes('sunny')) return '☀️';
    if(d.includes('snow')) return '❄️';
    if(d.includes('storm') || d.includes('thunder')) return '⛈️';
    if(d.includes('drizzle')) return '🌦️';
    if(d.includes('mist') || d.includes('fog')) return '🌫️';
    return '🌤️';
  }

  async function fetchWeather(city){
    setLoading();
    try{
      const urlCurrent = `api/weather_public.php?city=${encodeURIComponent(city)}&type=current`;
      const urlForecast = `api/weather_public.php?city=${encodeURIComponent(city)}&type=forecast`;

      const [resCur, resFor] = await Promise.all([
        fetch(urlCurrent, {cache: 'no-store'}),
        fetch(urlForecast, {cache: 'no-store'})
      ]);

      if(!resCur.ok || !resFor.ok) throw new Error('Network response not ok');

      const jsonCur = await resCur.json();
      const jsonFor = await resFor.json();

      let cur = jsonCur;
      if (jsonCur && jsonCur.success && jsonCur.data) cur = jsonCur.data;
      let forP = jsonFor;
      if (jsonFor && jsonFor.success && jsonFor.data) forP = jsonFor.data;

      // normalize current
      const visibilityMeters = (cur.visibility != null) ? cur.visibility : (cur.current && cur.current.visibility) || null;
      const visibilityKm = (visibilityMeters != null && !isNaN(visibilityMeters)) ? (visibilityMeters / 1000) : null;

      const data = {
        city: cur.name || cur.city || city,
        country: (cur.sys && cur.sys.country) || (forP.city && forP.city.country) || 'ID',
        temp_c: (cur.main && cur.main.temp) != null ? cur.main.temp : null,
        feels_like: (cur.main && cur.main.feels_like) != null ? cur.main.feels_like : null,
        description: (cur.weather && cur.weather[0] && cur.weather[0].description) || cur.description || '',
        humidity: (cur.main && cur.main.humidity) != null ? cur.main.humidity : null,
        wind_speed: (cur.wind && cur.wind.speed) != null ? cur.wind.speed : null,
        pressure: (cur.main && cur.main.pressure) != null ? cur.main.pressure : null,
        visibility: visibilityKm,
        extra: { current: cur, forecast: forP }
      };

      render(data);

      // Render hourly and daily from forecast payload (OpenWeather 3-hourly list)
      renderForecast(forP);

    }catch(err){
      // fallback mock
      const mock = {
        city: city,
        temp_c: 26,
        description: 'Clouds',
        humidity: 68,
        wind_speed: 4.46,
        pressure: 1012,
        visibility: 10,
        extra: 'Mock data used because API request failed.'
      };
      render(mock);
      console.warn('Weather fetch failed:', err);
    }
  }

  // Fetch only current (used for polling)
  async function fetchCurrent(city){
    try{
      const res = await fetch(`api/weather_public.php?city=${encodeURIComponent(city)}&type=current`, {cache: 'no-store'});
      if(!res.ok) throw new Error('Network response not ok');
      const json = await res.json();
      let cur = json;
      if (json && json.success && json.data) cur = json.data;

      const visibilityMeters = (cur.visibility != null) ? cur.visibility : (cur.current && cur.current.visibility) || null;
      const visibilityKm = (visibilityMeters != null && !isNaN(visibilityMeters)) ? (visibilityMeters / 1000) : null;

      const data = {
        city: cur.name || cur.city || city,
        country: (cur.sys && cur.sys.country) || 'ID',
        temp_c: (cur.main && cur.main.temp) != null ? cur.main.temp : null,
        feels_like: (cur.main && cur.main.feels_like) != null ? cur.main.feels_like : null,
        description: (cur.weather && cur.weather[0] && cur.weather[0].description) || cur.description || '',
        humidity: (cur.main && cur.main.humidity) != null ? cur.main.humidity : null,
        wind_speed: (cur.wind && cur.wind.speed) != null ? cur.wind.speed : null,
        pressure: (cur.main && cur.main.pressure) != null ? cur.main.pressure : null,
        visibility: visibilityKm,
        extra: { current: cur }
      };
      render(data);
    }catch(e){
      console.warn('fetchCurrent failed', e);
    }
  }

  // Fetch only forecast (used for polling)
  async function fetchForecast(city){
    try{
      const res = await fetch(`api/weather_public.php?city=${encodeURIComponent(city)}&type=forecast`, {cache: 'no-store'});
      if(!res.ok) throw new Error('Network response not ok');
      const json = await res.json();
      let forP = json;
      if (json && json.success && json.data) forP = json.data;
      renderForecast(forP);
    }catch(e){
      console.warn('fetchForecast failed', e);
    }
  }

  function renderForecast(forecastPayload){
    const hourlyRow = document.getElementById('hourlyRow');
    const dailyRow = document.getElementById('dailyRow');
    if(!forecastPayload || !forecastPayload.list) return;

    // hourly: take first 12 items (3-hourly steps) and format time
    hourlyRow.innerHTML = '';
    const hours = forecastPayload.list.slice(0, 12);
    hours.forEach(item => {
      const d = new Date(item.dt * 1000);
      const hour = d.getHours().toString().padStart(2, '0') + ':00';
      const icon = (item.weather && item.weather[0] && item.weather[0].main) || '';
      const tempVal = displayTemp(item.main.temp);
      const card = document.createElement('div');
      card.className = 'hourly-card';
      card.innerHTML = `<div class="time">${hour}</div><div class="h-icon">${iconFor(icon)}</div><div class="h-temp">${tempVal}°</div>`;
      hourlyRow.appendChild(card);
    });

    // daily: group by date, compute min/max
    const days = {};
    forecastPayload.list.forEach(item => {
      const d = new Date(item.dt * 1000);
      const key = d.toISOString().slice(0,10);
      if(!days[key]) days[key] = {temps: [], icons: [], dt: d.getTime()};
      days[key].temps.push(item.main.temp);
      if(item.weather && item.weather[0]) days[key].icons.push(item.weather[0].main);
    });

    const dayKeys = Object.keys(days).slice(0,5);
    dailyRow.innerHTML = '';
    dayKeys.forEach(key => {
      const info = days[key];
      const minVal = displayTemp(Math.min(...info.temps));
      const maxVal = displayTemp(Math.max(...info.temps));
      const icon = info.icons.length ? info.icons[0] : '';
      const d = new Date(info.dt);
      const dayName = d.toLocaleDateString(undefined, {weekday:'short'});
      const card = document.createElement('div');
      card.className = 'daily-card';
      card.innerHTML = `<div class="d-day">${dayName}</div><div class="d-icon">${iconFor(icon)}</div><div class="d-temp">${maxVal}° / ${minVal}°</div>`;
      dailyRow.appendChild(card);
    });
  }

  searchBtn.addEventListener('click', ()=>{
    const q = cityInput.value.trim();
    if(!q) return; fetchWeather(q);
  });
  cityInput.addEventListener('keyup', (e)=>{ if(e.key==='Enter'){ searchBtn.click(); }});

  if (toggleBtn) {
    toggleBtn.addEventListener('click', ()=>{
      moreInfo.classList.toggle('open');
      toggleBtn.textContent = moreInfo.classList.contains('open') ? 'Hide Details ▴' : 'View Details ▾';
    });
  }

  unitToggle.addEventListener('click', ()=>{
    currentUnit = currentUnit === 'C' ? 'F' : 'C';
    localStorage.setItem('tempUnit', currentUnit);
    updateUnitButton();
    // Re-render with new unit
    if (currentWeatherData) {
      render(currentWeatherData);
      if (currentWeatherData.extra && currentWeatherData.extra.forecast) {
        renderForecast(currentWeatherData.extra.forecast);
      }
    }
  });
  updateUnitButton();

  // Load default city from query string if provided, otherwise fallback
  const params = new URLSearchParams(window.location.search);
  const defaultCity = params.get('city') || 'Kumbakonam';
  cityInput.value = defaultCity;
  // If server embedded initial data is available, use it immediately
  console.log('__INITIAL_WEATHER__:', window.__INITIAL_WEATHER__);
  if (window.__INITIAL_WEATHER__) {
    try{
      const init = window.__INITIAL_WEATHER__;
      console.log('Processing initial:', init);
      if (init.current) {
        const cur = init.current;
        const visibilityMeters = (cur.visibility != null) ? cur.visibility : (cur.current && cur.current.visibility) || null;
        const visibilityKm = (visibilityMeters != null && !isNaN(visibilityMeters)) ? (visibilityMeters / 1000) : null;
        const data = {
          city: init.city || (cur.name || cur.city) || defaultCity,
          country: (cur.sys && cur.sys.country) || (init.forecast && init.forecast.city && init.forecast.city.country) || 'ID',
          temp_c: (cur.main && cur.main.temp) != null ? cur.main.temp : null,
          feels_like: (cur.main && cur.main.feels_like) != null ? cur.main.feels_like : null,
          description: (cur.weather && cur.weather[0] && cur.weather[0].description) || cur.description || '',
          humidity: (cur.main && cur.main.humidity) != null ? cur.main.humidity : null,
          wind_speed: (cur.wind && cur.wind.speed) != null ? cur.wind.speed : null,
          pressure: (cur.main && cur.main.pressure) != null ? cur.main.pressure : null,
          visibility: visibilityKm,
          extra: { current: cur, forecast: init.forecast }
        };
        console.log('Final data:', data);
        render(data);
      }
      if (init.forecast) {
        console.log('Rendering forecast:', init.forecast);
        renderForecast(init.forecast);
      }
    }catch(e){
      console.warn('initial data parse failed', e);
      fetchWeather(defaultCity);
    }
  } else {
    console.warn('No initial data, fetching');
    fetchWeather(defaultCity);
  }

  // Polling: update current every 2 minutes, forecast every 30 minutes
  setInterval(()=> fetchCurrent(defaultCity), 2 * 60 * 1000);
  setInterval(()=> fetchForecast(defaultCity), 30 * 60 * 1000);

})();
