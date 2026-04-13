(() => {
    const state = {
        activeElementBeforeModal: null,
        loaderTimeoutId: null,
    };

    const ensureContainers = () => {
        if (!document.getElementById("globalLoader")) {
            const loader = document.createElement("div");
            loader.id = "globalLoader";
            loader.className = "wwa-global-loader hidden";
            loader.innerHTML = `<div class="wwa-global-spinner" aria-label="Loading"></div>`;
            document.body.appendChild(loader);
        }
        if (!document.getElementById("toastContainer")) {
            const toastContainer = document.createElement("div");
            toastContainer.id = "toastContainer";
            toastContainer.className = "wwa-toast-container";
            document.body.appendChild(toastContainer);
        }
        if (!document.getElementById("confirmModal")) {
            const modal = document.createElement("div");
            modal.id = "confirmModal";
            modal.className = "wwa-modal-backdrop hidden";
            modal.innerHTML = `
                <div class="wwa-modal-panel" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
                    <h2 id="confirmTitle" class="text-white text-lg font-semibold mb-2">Confirm Action</h2>
                    <p class="text-slate-400 mb-4" id="confirmMessage">Are you sure?</p>
                    <div class="flex justify-end gap-3">
                        <button id="cancelBtn" class="px-4 py-2 bg-slate-700 text-white rounded-lg">Cancel</button>
                        <button id="confirmBtn" class="px-4 py-2 bg-red-600 text-white rounded-lg">Confirm</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
    };

    const showLoader = () => {
        const loader = document.getElementById("globalLoader");
        if (!loader) return;
        if (state.loaderTimeoutId) {
            clearTimeout(state.loaderTimeoutId);
            state.loaderTimeoutId = null;
        }
        loader.classList.remove("hidden");
        loader.classList.add("wwa-loader-open");
        // Failsafe: never let loader block the UI forever.
        state.loaderTimeoutId = window.setTimeout(() => {
            hideLoader();
        }, 1500);
    };

    const hideLoader = () => {
        const loader = document.getElementById("globalLoader");
        if (!loader) return;
        if (state.loaderTimeoutId) {
            clearTimeout(state.loaderTimeoutId);
            state.loaderTimeoutId = null;
        }
        loader.classList.remove("wwa-loader-open");
        loader.classList.add("hidden");
    };

    const showToast = (message, type = "success") => {
        const container = document.getElementById("toastContainer");
        if (!container || !message) return;
        const toast = document.createElement("div");
        toast.className = `wwa-toast ${type === "error" ? "error" : "success"}`;
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => {
            toast.classList.add("wwa-toast-leave");
            setTimeout(() => toast.remove(), 240);
        }, 2800);
    };

    const showConfirm = (message, onConfirm) => {
        const modal = document.getElementById("confirmModal");
        const msg = document.getElementById("confirmMessage");
        const confirmBtn = document.getElementById("confirmBtn");
        const cancelBtn = document.getElementById("cancelBtn");
        if (!modal || !msg || !confirmBtn || !cancelBtn) return;

        state.activeElementBeforeModal = document.activeElement;
        msg.textContent = message || "Are you sure?";
        modal.classList.remove("hidden");
        modal.classList.add("wwa-modal-open");
        confirmBtn.focus();

        const cleanup = () => {
            modal.classList.remove("wwa-modal-open");
            modal.classList.add("hidden");
            confirmBtn.onclick = null;
            cancelBtn.onclick = null;
            modal.onclick = null;
            if (state.activeElementBeforeModal && typeof state.activeElementBeforeModal.focus === "function") {
                state.activeElementBeforeModal.focus();
            }
        };

        confirmBtn.onclick = () => {
            cleanup();
            if (typeof onConfirm === "function") onConfirm();
        };
        cancelBtn.onclick = cleanup;
        modal.onclick = (e) => {
            if (e.target === modal) cleanup();
        };
        document.onkeydown = (e) => {
            if (e.key === "Escape") {
                cleanup();
                document.onkeydown = null;
            }
        };
    };

    const setButtonLoading = (btn, text = "Loading...") => {
        if (!btn || btn.dataset.loading === "1") return;
        const original = btn.innerHTML;
        btn.dataset.originalHtml = original;
        btn.dataset.loading = "1";
        btn.disabled = true;
        btn.classList.add("opacity-90", "cursor-not-allowed");
        btn.innerHTML = `
            <span class="inline-flex items-center gap-2">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                </svg>
                <span>${text}</span>
            </span>
        `;
    };

    const initSkeleton = () => {
        window.addEventListener("load", () => {
            hideLoader();
        });
    };

    const initRuntimeSafety = () => {
        // Handle browser back/forward cache restore: always clear stale loader state.
        window.addEventListener("pageshow", () => {
            hideLoader();
        });

        window.addEventListener("popstate", () => {
            hideLoader();
        });

        window.addEventListener("error", () => {
            hideLoader();
        });

        window.addEventListener("unhandledrejection", () => {
            hideLoader();
        });
    };

    const safeFetchJSON = (input, init = {}) => {
        showLoader();
        return fetch(input, init)
            .then((res) => {
                if (!res.ok) {
                    throw new Error(`HTTP ${res.status}`);
                }
                return res.json();
            })
            .catch((error) => {
                showToast("Request failed", "error");
                throw error;
            })
            .finally(() => {
                hideLoader();
            });
    };

    const clearHighlights = (root) => {
        root.querySelectorAll("mark.wwa-mark").forEach((mark) => {
            const text = document.createTextNode(mark.textContent || "");
            mark.replaceWith(text);
        });
    };

    const highlightText = (element, needle) => {
        if (!needle || !element) return;
        const text = element.textContent || "";
        const idx = text.toLowerCase().indexOf(needle.toLowerCase());
        if (idx < 0) return;
        const before = text.slice(0, idx);
        const match = text.slice(idx, idx + needle.length);
        const after = text.slice(idx + needle.length);
        element.innerHTML = `${before}<mark class="wwa-mark">${match}</mark>${after}`;
    };

    const initSearchUX = () => {
        document.querySelectorAll("[data-search-input]").forEach((input) => {
            const targetSelector = input.getAttribute("data-search-target") || "";
            const rows = targetSelector ? Array.from(document.querySelectorAll(targetSelector)) : [];
            const emptyId = input.getAttribute("data-search-empty-id") || "";
            const emptyEl = emptyId ? document.getElementById(emptyId) : null;
            if (!rows.length) return;

            input.addEventListener("input", () => {
                const q = (input.value || "").trim().toLowerCase();
                let visible = 0;
                rows.forEach((row) => {
                    clearHighlights(row);
                    const txt = (row.innerText || "").toLowerCase();
                    const match = q === "" || txt.includes(q);
                    row.style.display = match ? "" : "none";
                    if (match) {
                        visible++;
                        if (q) {
                            const firstCell = row.querySelector("td");
                            if (firstCell) highlightText(firstCell, q);
                        }
                    }
                });
                if (emptyEl) {
                    emptyEl.classList.toggle("hidden", visible > 0);
                }
            });
        });
    };

    const initClipboard = () => {
        document.querySelectorAll(".copy-btn[data-link], [data-copy-text]").forEach((btn) => {
            btn.addEventListener("click", async () => {
                const text = btn.getAttribute("data-copy-text") || btn.getAttribute("data-link") || "";
                if (!text) return;
                try {
                    await navigator.clipboard.writeText(text);
                    showToast("Copied!", "success");
                } catch (error) {
                    showToast("Failed to copy", "error");
                }
            });
        });
    };

    const initImagePreview = () => {
        const modal = document.createElement("div");
        modal.className = "wwa-image-modal hidden";
        modal.innerHTML = `<img alt="Preview" class="wwa-image-modal-img">`;
        document.body.appendChild(modal);
        const img = modal.querySelector(".wwa-image-modal-img");
        document.querySelectorAll("a[data-image-preview]").forEach((link) => {
            link.addEventListener("click", (e) => {
                e.preventDefault();
                const href = link.getAttribute("href");
                if (!href || !img) return;
                img.setAttribute("src", href);
                modal.classList.remove("hidden");
            });
        });
        modal.addEventListener("click", () => modal.classList.add("hidden"));
    };

    ensureContainers();
    window.showToast = showToast;
    window.showConfirm = showConfirm;
    window.showLoader = showLoader;
    window.hideLoader = hideLoader;
    window.safeFetchJSON = safeFetchJSON;

    initSkeleton();
    initRuntimeSafety();
    initSearchUX();
    initClipboard();
    initImagePreview();

    document.querySelectorAll("form[data-submit-spinner]").forEach((form) => {
        form.addEventListener("submit", () => {
            const btn = form.querySelector("button[type='submit']");
            setButtonLoading(btn, "Processing...");
        });
    });

    document.querySelectorAll("form[data-confirm]").forEach((form) => {
        form.addEventListener("submit", (event) => {
            if (form.dataset.confirmed === "1") {
                form.dataset.confirmed = "0";
                return;
            }
            event.preventDefault();
            const message = form.getAttribute("data-confirm-message") || "Are you sure?";
            showConfirm(message, () => {
                form.dataset.confirmed = "1";
                form.submit();
            });
        });
    });

    document.querySelectorAll("a[data-confirm-link]").forEach((link) => {
        link.addEventListener("click", (event) => {
            event.preventDefault();
            const message = link.getAttribute("data-confirm-message") || "Are you sure?";
            const href = link.getAttribute("href") || "#";
            showConfirm(message, () => {
                window.location.href = href;
            });
        });
    });

    document.querySelectorAll("a[href]").forEach((link) => {
        if (link.hasAttribute("data-confirm-link")) return;
        link.addEventListener("click", (event) => {
            const href = link.getAttribute("href") || "";
            if (!href || href.startsWith("#") || href.startsWith("javascript:") || link.target === "_blank") return;
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            if (link.hasAttribute("data-loader-global")) {
                showLoader();
            }
        });
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "/" && !(event.target instanceof HTMLInputElement) && !(event.target instanceof HTMLTextAreaElement)) {
            const search = document.querySelector("[data-search-input], #searchInput");
            if (search && typeof search.focus === "function") {
                event.preventDefault();
                search.focus();
            }
        }
    });

    const toastEl = document.querySelector("[data-toast]");
    if (toastEl) {
        const type = toastEl.getAttribute("data-toast-type") || "success";
        showToast(toastEl.textContent || "", type);
    }
})();
