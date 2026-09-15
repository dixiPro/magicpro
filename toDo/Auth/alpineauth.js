(function () {
  //
  // api.js
  //
  async function apiCall(url = '', data = {}, logResult = false) {
    return new Promise(async (resolve, reject) => {
      if (url == '') {
        reject('Ошибка в запросе');
        return;
      }
      let response, apiResult;

      if (logResult) {
        console.log('apiCall Start');
        console.log('apiCall url', url);
        console.log('apiCall data', data);
      }
      try {
        response = await fetch(url, {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLapiCallRequest',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(data),
        });

        if (!response.ok) {
          reject('Ошибка сети');
          return;
        }
      } catch (error) {
        reject('Ошибка сети');
        return;
      }

      try {
        apiResult = await response.json();
        if (logResult) {
          console.log('apiCall apiResult', apiResult);
        }
      } catch (error) {
        reject('Невалидный ответ');
        return;
      } finally {
        if (logResult) {
          console.log('apiCall end', apiResult);
        }
      }

      if (Boolean(apiResult?.status)) {
        resolve(apiResult);
        return;
      } else {
        reject(apiResult?.errorMsg || 'Неизвестная ошибка');
        return;
      }
    });
  }

  //
  // login.js
  //
  async function login(email, password) {
    try {
      const response = await fetch('/api/sessauth/auth', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLapiCallRequest',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          email,
          password,
          remember_me: 1,
        }),
      });

      if (!response.ok) {
        throw new Error('Ошибка сети');
      }

      const apiResult = await response.json();

      if (apiResult?.status) {
        return;
      }

      throw new Error('Неверный пароль');
    } catch (error) {
      throw new Error(error.message || 'Произошла ошибка');
    }
  }

  //
  // getNavigatorInfo.js
  //
  function getNavigatorInfo() {
    function detectWebView() {
      const ua = navigator.userAgent.toLowerCase();
      let result = {
        isWebView: false,
        method: 'none',
        userAgent: navigator.userAgent,
      };

      const isAndroidWebView = /wv/.test(ua) || /version\/\d+\.\d+/.test(ua);
      const isIOSWebView = /mobile\//.test(ua) && !/safari\//.test(ua);
      if (isAndroidWebView || isIOSWebView) {
        result.isWebView = true;
        result.method = 'userAgent';
        return result;
      }

      const lacksBrowserFeatures = !window.chrome && !navigator.standalone;
      if (lacksBrowserFeatures) {
        result.isWebView = true;
        result.method = 'browserFeatures';
        return result;
      }

      if (!navigator.userAgentData) {
        if (/wv/.test(ua)) {
          result.isWebView = true;
          result.method = 'userAgentData + userAgent';
          return result;
        }
      }

      if (ua.indexOf('wv') > -1) {
        result.isWebView = true;
        result.method = 'Gpt1';
        return result;
      }

      if (/iP(hone|od|ad)/.test(ua) && /AppleWebKit/.test(ua) && !/Safari/.test(ua)) {
        result.isWebView = true;
        result.method = 'Gpt2';
        return result;
      }

      return result;
    }

    const navigatorInfo = {
      detectWebView: detectWebView(),
      userAgent: navigator.userAgent,
      language: navigator.language || navigator.userLanguage,
      userAgentData: navigator?.userAgentData,
      platform: navigator?.platform,
      vendor: navigator?.vendor,
      appVersion: navigator?.appVersion,
      cookieEnabled: navigator.cookieEnabled,

      hardwareConcurrency: navigator.hardwareConcurrency || 'unknown',
      deviceMemory: navigator.deviceMemory || 'unknown',
      connection: navigator.connection
        ? {
            effectiveType: navigator.connection.effectiveType,
            downlink: navigator.connection.downlink,
            rtt: navigator.connection.rtt,
          }
        : 'unknown',
      webdriver: navigator.webdriver || false,
      doNotTrack: navigator.doNotTrack || 'unspecified',
      screenInfo: {
        width: screen.width,
        height: screen.height,
        pixelDepth: screen.pixelDepth,
      },
    };

    return navigatorInfo;
  }

  //
  // checkUserRobot.js
  //
  function loadScriptToHead(scriptUrl) {
    return new Promise((resolve) => {
      const existingScript = document.querySelector(`script[src="${scriptUrl}"]`);
      if (existingScript) {
        resolve(true);
        return;
      }

      const script = document.createElement('script');
      script.src = scriptUrl;
      script.async = true;
      document.head.appendChild(script);
      script.onload = () => {
        resolve(true);
        return;
      };

      script.onerror = () => {
        resolve(false);
        return;
      };
    });
  }

  async function grecaptchaGetToken(key) {
    return new Promise((resolve) => {
      if (typeof grecaptcha == 'undefined') {
        resolve('');
        return;
      }

      grecaptcha.ready(() => {
        grecaptcha
          .execute(key, { action: 'homepage' })
          .then((token) => {
            resolve(token);
            return;
          })
          .catch((error) => {
            resolve('');
            return;
          });
      });
    });
  }

  async function showModalPrompt(question) {
    return new Promise((resolve) => {
      const modal = document.createElement('div');
      modal.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0,0,0,0.3);
        z-index: 2500;
      `;

      const overlay = document.createElement('div');
      overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 2000;
      `;

      modal.innerHTML = `
      <div class="mw400">
        <h5 class="my-0">Антиробот проверка</h5>
        <div class='my-2 small'>орфографические <strong>а</strong>шибки запланированы</div>
        <div class='my-2'>${question}:</div>
        <div class='my-2'>
        <input type="text" class="form-control form-control-sm" id="modalInput" autocomplete="off" >
        </div>
        <button class="btn btn-sm green" id="modalSubmit">Отправить</button>
      </div>
      `;

      document.body.appendChild(overlay);
      document.body.appendChild(modal);

      const input = modal.querySelector('#modalInput');
      input.focus();

      const submitBtn = modal.querySelector('#modalSubmit');

      const submitHandler = () => {
        const answer = input.value.trim();
        cleanup();
        resolve(answer);
      };

      submitBtn.onclick = submitHandler;

      input.onkeypress = (e) => {
        if (e.key === 'Enter') {
          submitHandler();
        }
      };

      function cleanup() {
        document.body.removeChild(overlay);
        document.body.removeChild(modal);
      }
    });
  }

  async function checkUserRobot(recaptureKey, email) {
    return new Promise(async (resolve, reject) => {
      const urlScript = `https://www.google.com/recaptcha/api.js?render=${recaptureKey}`;
      try {
        const result = await loadScriptToHead(urlScript);
        let gToken = '';

        if (result) {
          gToken = await grecaptchaGetToken(recaptureKey);
        }

        const apiResult_gtoken = await apiCall('/apiAuthGoogleRecapture', {
          gToken: gToken,
          email: email,
          navigator: getNavigatorInfo(),
        });

        if (Boolean(apiResult_gtoken.gCapture)) {
          resolve(apiResult_gtoken);
          return;
        }

        let apiRes = apiResult_gtoken;
        while (true) {
          const userAnswer = await showModalPrompt(apiRes.userQuestion);
          apiRes = await apiCall('/apiAuthUserRecapture', {
            email: email,
            askToken: apiRes.askToken,
            userAnswer: userAnswer,
            navigator: getNavigatorInfo(),
          });

          if (Boolean(apiRes.gCapture)) {
            resolve(apiRes);
            return;
          }
        }
      } catch (error) {
        reject(error);
        return;
      }
    });
  }

  //
  // валидация email (screens/email.js)
  //
  function validEmail(email) {
    const re =
      /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
  }

  //
  // регистрация всех Alpine.store/Alpine.data после alpine:init,
  // до Alpine.start() (см. порядок <script> в newauth)
  //
  document.addEventListener('alpine:init', () => {
    // store.js
    Alpine.store('auth', {
      email: '',
      password: '',
      emailToken: '',
      passToken: '',
      userExist: true,
      redirect: document.getElementById('login-app')?.dataset?.redirect || '/cabinet',

      step: 'email', // 'email' | 'register' | 'password' | 'forgot-password' | 'code'

      errors: [],
      _errorSeq: 0,

      addError(message) {
        this._errorSeq += 1;
        const id = this._errorSeq;
        const text = message instanceof Error ? message.message : message;
        this.errors.push({ id, text });
        setTimeout(() => this.removeError(id), 6000);
      },

      removeError(id) {
        this.errors = this.errors.filter((e) => e.id !== id);
      },

      goTo(step) {
        if (step !== 'email' && !this.emailToken) {
          this.clearStore();
          this.step = 'email';
          return;
        }
        this.step = step;
      },

      clearStore() {
        this.email = '';
        this.password = '';
        this.emailToken = '';
        this.passToken = '';
        this.userExist = false;
        this.redirect = document.getElementById('login-app')?.dataset?.redirect || '/cabinet';
      },
    });

    // passwordField.js
    Alpine.data('passwordField', (autocomplete = 'current-password') => ({
      type: 'password',
      autocomplete,

      init() {
        const socreg = document.getElementById('socreg');
        if (socreg != null) {
          socreg.style.display = 'none';
        }
      },

      toggle() {
        this.type = this.type === 'password' ? 'text' : 'password';
      },
    }));

    // screens/email.js
    Alpine.data('emailScreen', () => ({
      pageLoading: false,

      async checkEmail() {
        const auth = this.$store.auth;

        if (!validEmail(auth.email)) {
          auth.addError('Ошибка в e-mail');
          return;
        }

        this.pageLoading = true;
        try {
          const recaptureKey = document.getElementById('login-app')?.dataset?.recapturekey;
          const result = await checkUserRobot(recaptureKey, auth.email);
          auth.emailToken = result.emailToken;

          if (Boolean(result?.userExist)) {
            auth.userExist = true;
            auth.goTo('password');
          } else {
            auth.userExist = false;
            auth.goTo('register');
          }
        } catch (e) {
          auth.addError(e);
        } finally {
          this.pageLoading = false;
        }
      },
    }));

    // screens/register.js
    Alpine.data('registerScreen', () => ({
      pageLoading: false,

      async register(ev) {
        ev.preventDefault();
        const auth = this.$store.auth;

        this.pageLoading = true;
        try {
          const response = await apiCall('/apiAuthSendRegisterEmail', {
            emailToken: auth.emailToken,
            password: auth.password,
            redirect: auth.redirect,
          });
          auth.passToken = response.passToken;
          auth.goTo('code');
        } catch (e) {
          auth.addError(e);
        } finally {
          this.pageLoading = false;
        }
      },
    }));

    // screens/password.js
    Alpine.data('passwordScreen', () => ({
      pageLoading: false,
      state: 'start', // 'start' | 'success'

      async loginToSite(ev) {
        ev.preventDefault();
        const auth = this.$store.auth;

        this.pageLoading = true;
        try {
          await login(auth.email, auth.password);
          this.state = 'success';
        } catch (error) {
          auth.addError(error.message);
        } finally {
          this.pageLoading = false;
        }
      },
    }));

    // screens/forgotPassword.js
    Alpine.data('forgotPasswordScreen', () => ({
      pageLoading: false,
      passwordSend: false,
      seconds: 0,

      delaySending() {
        this.passwordSend = true;
        this.seconds = 60;
        const intervalId = setInterval(() => {
          this.seconds--;
          if (this.seconds < 0) {
            clearInterval(intervalId);
            this.passwordSend = false;
          }
        }, 1000);
      },

      async sendCode() {
        const auth = this.$store.auth;

        this.pageLoading = true;
        try {
          await apiCall('/apiAuthRequestNewPass', {
            emailToken: auth.emailToken,
            redirect: auth.redirect,
          });
          this.delaySending();
        } catch (e) {
          auth.addError(e);
        } finally {
          this.pageLoading = false;
        }
      },
    }));

    // screens/enterCode.js
    Alpine.data('enterCodeScreen', () => ({
      pageLoading: false,
      code: '',
      state: 'start', // 'start' | 'success'

      async enterCode() {
        const auth = this.$store.auth;

        this.pageLoading = true;
        try {
          await apiCall('/apiAuthRegisterUser', {
            passToken: auth.passToken,
            code: this.code,
          });

          await login(auth.email, auth.password);
          auth.clearStore();
          this.state = 'success';
        } catch (e) {
          auth.addError(e);
        } finally {
          this.pageLoading = false;
        }
      },
    }));
  });
})();