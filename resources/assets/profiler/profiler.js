const profiler = (function(window, document) {
    'use strict';

    const requestsObserver = {
        observer: new PerformanceObserver((list) => {
            let fetched = false;
            for (const entry of list.getEntries()) {
                if (entry.initiatorType === 'fetch') {
                    fetched = true;
                    console.log('Fetch request detected to', entry.name);
                }
            }

            if (fetched) {
                profiler.updateProfileBarProfiles();
            }
        }),
        observe: function() {
            this.observer.observe({entryTypes: ['resource']});
        },
        disconnect: function() {
            this.observer.disconnect();
        }
    };
    
    const profiler = {
        initVardumps: function() {
            document.querySelectorAll('.profiler-dump').forEach(el => {
                el.addEventListener('click', (e) => {
                    if (e.target.hasAttribute('data-profiler-state')) {
                        e.target.nextSibling.classList.toggle('closed');
                        
                        if (e.target.getAttribute('data-profiler-state') === '1') {
                            e.target.setAttribute('data-profiler-state', '0');
                            e.target.innerHTML = ' ▶ ';
                        } else {
                            e.target.setAttribute('data-profiler-state', '1');
                            e.target.innerHTML = ' ▼ ';
                        }
                    }
                });
            });
            
            document.querySelectorAll('.profiler-dump [data-depth]').forEach(el => {
                const wrapperEl = document.createElement('span');
                const toggleEl = document.createElement('span');
                const textNode = document.createTextNode(' ▶ ');
                toggleEl.setAttribute('data-profiler-state', '0');
                toggleEl.appendChild(textNode);
                toggleEl.classList.add('link');
                wrapperEl.classList.toggle('closed');
                el.parentNode.insertBefore(wrapperEl, el);
                wrapperEl.appendChild(el);
                wrapperEl.parentNode.insertBefore(toggleEl, wrapperEl);
            });
        },
        initToggle: function() {
            document.querySelectorAll('[data-profiler-toggle]').forEach(el => {
                const toggle = JSON.parse(el.getAttribute('data-profiler-toggle'));
                
                el.addEventListener('click', (e) => {
                    if (typeof toggle['el'] !== 'undefined') {
                        document.querySelector(toggle.el).classList.toggle(toggle.class);
                    } else {
                        el.classList.toggle(toggle.class);
                    }
                });
            });
        },
        updateProfileBarProfiles: function() {
            // update only profiles selection
            const inputEl = document.querySelector('select[name="profiler_profile"]');
            const form = inputEl.closest('form');
            
            requestsObserver.disconnect();
            
            fetch(form.action, {
                method: form.getAttribute('method'),
                body: new FormData(form)
            }).then(response => {
                if (response.status !== 200) {
                    throw new Error('Invalid response');
                }
                return response.json();
            }).then(data => {
                const doc = (new DOMParser()).parseFromString(data.profile_html, 'text/html');
                document.querySelector('#profiler').replaceWith(doc.querySelector('#profiler'));                
                this.updateProfileBarProfile();
                this.initVardumps();
                this.initToggle();
                requestsObserver.observe();
            }).catch(e => {
                //requestsObserver.observe();
            });
        },
        updateProfileBarProfile: function() {
            const inputEl = document.querySelector('select[name="profiler_profile"]');
            
            inputEl.addEventListener('change', (e) => {
                const form = e.target.closest('form');
                requestsObserver.disconnect();
                
                // substract one as not to get more profiles shonw.
                const countEl = document.querySelector('[name="profiler_profiles_count"]');
                countEl.value = parseInt(countEl.value)-1;
                
                fetch(form.action, {
                    method: form.getAttribute('method'),
                    body: new FormData(form),
                }).then(response => {
                    if (response.status !== 200) {
                        throw new Error('Invalid response');
                    }
                    return response.json();
                }).then(data => {
                    const doc = (new DOMParser()).parseFromString(data.profile_html, 'text/html');
                    const newEl = doc.querySelector('#profiler');
                    newEl.querySelector('#profiler-content').classList.add('profiler-open');
                    document.querySelector('#profiler').replaceWith(newEl);
                    
                    requestsObserver.observe();
                    this.initVardumps();
                    this.initToggle();
                    this.updateProfileBarProfile();
                }).catch(e => {
                    //
                });
            });
        }
    };

    document.addEventListener('DOMContentLoaded', (e) => {
        profiler.initVardumps();
        profiler.initToggle();
        
        if (document.querySelector('#profiler')) {
            requestsObserver.observe();
            profiler.updateProfileBarProfile();            
        }
    });
    
    return profiler;
    
})(window, document);

export default profiler;