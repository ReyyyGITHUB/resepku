document.addEventListener("DOMContentLoaded", function () {
    var firstInput = document.querySelector(".login-form__input");

    if (firstInput) {
        firstInput.addEventListener("invalid", function () {
            firstInput.classList.add("is-invalid");
        });
    }

    var guestMode = document.body.dataset.guestMode === "1";
    var guestModal = document.querySelector("[data-guest-modal]");
    var guestTriggers = document.querySelectorAll("[data-guest-gate]");
    var csrfToken = document.body.dataset.csrfToken || "";
    var apiBase = document.body.dataset.apiBase || "../api/";
    var loginUrl = document.body.dataset.loginUrl || "../auth/login.php";
    var detailPage = document.body.classList.contains("detail-page");
    var profileEditModal = document.querySelector("[data-profile-edit-modal]");
    var profileEditOpeners = document.querySelectorAll("[data-profile-edit-open]");
    var profileEditClosers = document.querySelectorAll("[data-profile-edit-close]");
    var reportModal = document.querySelector("[data-report-modal]");
    var reportForm = document.querySelector("[data-report-form]");
    var reportPreview = document.querySelector("[data-report-target-preview]");
    var reportOpeners = document.querySelectorAll("[data-report-open]");
    var reportClosers = document.querySelectorAll("[data-report-close]");
    var reportSuccessFeedback = document.querySelector("[data-report-success-feedback]");
    var reportSuccessCountdown = document.querySelector("[data-report-success-countdown]");
    var reportSuccessRedirect = document.body.dataset.reportSuccessRedirect || "../home/";
    var reportSuccessDuration = 5000;
    var reportSuccessTimer = null;
    var reportSuccessInterval = null;
    var sidebarStorageKey = "resepku.sidebarCollapsed";
    var sidebarToggle = document.querySelector("[data-sidebar-toggle]");
    var sidebarTooltip = null;
    var detailImageOpeners = document.querySelectorAll("[data-detail-image-open]");
    var detailImageLightbox = document.querySelector("[data-detail-image-lightbox]");
    var detailImagePreview = detailImageLightbox ? detailImageLightbox.querySelector("[data-detail-image-preview]") : null;
    var detailImageCaption = detailImageLightbox ? detailImageLightbox.querySelector("[data-detail-image-caption]") : null;
    var detailImageClosers = detailImageLightbox ? detailImageLightbox.querySelectorAll("[data-detail-image-close]") : [];
    var activeDetailImageTrigger = null;

    function setSidebarCollapsed(collapsed) {
        document.documentElement.classList.toggle("sidebar-collapsed", collapsed);
        document.body.classList.toggle("sidebar-collapsed", collapsed);

        if (sidebarToggle) {
            sidebarToggle.setAttribute("aria-expanded", collapsed ? "false" : "true");
            sidebarToggle.setAttribute("aria-label", collapsed ? "Buka sidebar" : "Tutup sidebar");
        }
    }

    try {
        setSidebarCollapsed(window.localStorage.getItem(sidebarStorageKey) === "1");
    } catch (error) {
        setSidebarCollapsed(false);
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener("click", function () {
            var collapsed = !document.body.classList.contains("sidebar-collapsed");
            setSidebarCollapsed(collapsed);
            hideSidebarTooltip();

            try {
                window.localStorage.setItem(sidebarStorageKey, collapsed ? "1" : "0");
            } catch (error) {
                return;
            }
        });
    }

    function hideSidebarTooltip() {
        if (sidebarTooltip) {
            sidebarTooltip.remove();
            sidebarTooltip = null;
        }
    }

    function showSidebarTooltip(trigger) {
        if (!document.body.classList.contains("sidebar-collapsed")) {
            hideSidebarTooltip();
            return;
        }

        var label = trigger.getAttribute("data-sidebar-tooltip") || "";
        if (label === "") {
            return;
        }

        hideSidebarTooltip();
        sidebarTooltip = document.createElement("div");
        sidebarTooltip.className = "home-sidebar-floating-tooltip";
        sidebarTooltip.textContent = label;
        document.body.appendChild(sidebarTooltip);

        var rect = trigger.getBoundingClientRect();
        sidebarTooltip.style.left = Math.round(rect.right + 12) + "px";
        sidebarTooltip.style.top = Math.round(rect.top + rect.height / 2) + "px";
    }

    document.querySelectorAll("[data-sidebar-tooltip]").forEach(function (trigger) {
        trigger.addEventListener("mouseenter", function () {
            showSidebarTooltip(trigger);
        });
        trigger.addEventListener("focus", function () {
            showSidebarTooltip(trigger);
        });
        trigger.addEventListener("mouseleave", hideSidebarTooltip);
        trigger.addEventListener("blur", hideSidebarTooltip);
    });

    window.addEventListener("scroll", hideSidebarTooltip, true);
    window.addEventListener("resize", hideSidebarTooltip);

    function openGuestModal() {
        if (!guestModal) {
            return;
        }

        guestModal.classList.add("is-open");
        document.body.classList.add("modal-open");
    }

    function closeGuestModal() {
        if (!guestModal) {
            return;
        }

        guestModal.classList.remove("is-open");
        document.body.classList.remove("modal-open");
    }

    guestTriggers.forEach(function (trigger) {
        trigger.addEventListener("click", function (event) {
            if (guestMode && guestModal) {
                event.preventDefault();
                openGuestModal();
                return;
            }

            if (trigger.hasAttribute("data-action-toggle")) {
                trigger.classList.toggle("is-active");
            }
        });
    });

    if (guestModal) {
        guestModal.addEventListener("click", function (event) {
            if (event.target.hasAttribute("data-guest-close") || event.target.closest("[data-guest-close]")) {
                closeGuestModal();
            }
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                closeGuestModal();
            }
        });
    }

    function openProfileEditModal() {
        if (!profileEditModal) {
            return;
        }

        profileEditModal.classList.add("is-open");
        profileEditModal.setAttribute("aria-hidden", "false");
        document.body.classList.add("modal-open");
    }

    function closeProfileEditModal() {
        if (!profileEditModal) {
            return;
        }

        profileEditModal.classList.remove("is-open");
        profileEditModal.setAttribute("aria-hidden", "true");
        document.body.classList.remove("modal-open");
    }

    function openDetailImageLightbox(trigger) {
        if (!detailImageLightbox || !detailImagePreview || !trigger) {
            return;
        }

        activeDetailImageTrigger = trigger;
        detailImagePreview.setAttribute("src", trigger.dataset.detailImageSrc || "");
        detailImagePreview.setAttribute("alt", trigger.dataset.detailImageAlt || "");

        if (detailImageCaption) {
            detailImageCaption.textContent = trigger.dataset.detailImageCaption || "";
        }

        detailImageLightbox.classList.add("is-open");
        detailImageLightbox.setAttribute("aria-hidden", "false");
        document.body.classList.add("modal-open");
    }

    function closeDetailImageLightbox() {
        if (!detailImageLightbox || !detailImagePreview) {
            return;
        }

        detailImageLightbox.classList.remove("is-open");
        detailImageLightbox.setAttribute("aria-hidden", "true");
        detailImagePreview.setAttribute("src", "");
        detailImagePreview.setAttribute("alt", "");

        if (detailImageCaption) {
            detailImageCaption.textContent = "";
        }

        document.body.classList.remove("modal-open");

        if (activeDetailImageTrigger) {
            activeDetailImageTrigger.focus();
            activeDetailImageTrigger = null;
        }
    }

    function openReportModal(button) {
        if (!reportModal || !reportForm) {
            return;
        }

        var targetTypeInput = reportForm.querySelector('input[name="target_type"]');
        var targetIdInput = reportForm.querySelector('input[name="target_id"]');
        var targetType = button ? (button.dataset.reportTargetType || "") : "";
        var targetId = button ? (button.dataset.reportTargetId || "") : "";
        var targetLabel = button ? (button.dataset.reportTargetLabel || "target ini") : "target ini";

        if (targetTypeInput) {
            targetTypeInput.value = targetType;
        }

        if (targetIdInput) {
            targetIdInput.value = targetId;
        }

        if (reportPreview) {
            reportPreview.textContent = "Pengaduan akan dikirim untuk " + targetLabel + ".";
        }

        var title = reportModal.querySelector("#report-modal-title");
        if (title) {
            title.textContent = targetType === "pengguna" ? "Laporkan profil" : "Laporkan resep";
        }

        reportModal.classList.add("is-open");
        reportModal.setAttribute("aria-hidden", "false");
        document.body.classList.add("modal-open");
    }

    function closeReportModal() {
        if (!reportModal) {
            return;
        }

        reportModal.classList.remove("is-open");
        reportModal.setAttribute("aria-hidden", "true");
        document.body.classList.remove("modal-open");
    }

    function showReportSuccessFeedback() {
        if (!reportSuccessFeedback) {
            return false;
        }

        if (reportSuccessInterval) {
            window.clearInterval(reportSuccessInterval);
            reportSuccessInterval = null;
        }

        if (reportSuccessCountdown) {
            reportSuccessCountdown.textContent = "5";
            reportSuccessInterval = window.setInterval(function () {
                var nextValue = Math.max(Number(reportSuccessCountdown.textContent || "0") - 1, 0);
                reportSuccessCountdown.textContent = String(nextValue);

                if (nextValue <= 0 && reportSuccessInterval) {
                    window.clearInterval(reportSuccessInterval);
                    reportSuccessInterval = null;
                }
            }, 1000);
        }

        reportSuccessFeedback.classList.add("is-open");
        reportSuccessFeedback.setAttribute("aria-hidden", "false");
        document.body.classList.add("modal-open");
        return true;
    }

    profileEditOpeners.forEach(function (button) {
        button.addEventListener("click", openProfileEditModal);
    });

    profileEditClosers.forEach(function (button) {
        button.addEventListener("click", closeProfileEditModal);
    });

    if (profileEditModal) {
        profileEditModal.addEventListener("click", function (event) {
            if (event.target.matches(".profile-modal__backdrop")) {
                closeProfileEditModal();
            }
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                closeProfileEditModal();
            }
        });
    }

    detailImageOpeners.forEach(function (trigger) {
        trigger.addEventListener("click", function () {
            openDetailImageLightbox(trigger);
        });
    });

    detailImageClosers.forEach(function (button) {
        button.addEventListener("click", closeDetailImageLightbox);
    });

    if (detailImageLightbox) {
        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && detailImageLightbox.classList.contains("is-open")) {
                closeDetailImageLightbox();
            }
        });
    }

    reportOpeners.forEach(function (button) {
        button.addEventListener("click", function () {
            if (guestMode) {
                return;
            }

            openReportModal(button);
        });
    });

    reportClosers.forEach(function (button) {
        button.addEventListener("click", closeReportModal);
    });

    if (reportModal) {
        reportModal.addEventListener("click", function (event) {
            if (event.target.matches(".report-modal__backdrop")) {
                closeReportModal();
            }
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                closeReportModal();
            }
        });
    }

    document.querySelectorAll(".profile-filterbar").forEach(function (filterbar) {
        var profilePanel = filterbar.closest(".profile-panel");
        var recipeGrid = profilePanel ? profilePanel.querySelector("[data-profile-recipe-grid]") : null;

        filterbar.querySelectorAll("[data-profile-filter-action]").forEach(function (button) {
            button.addEventListener("click", function () {
                filterbar.querySelectorAll("[data-profile-filter-action]").forEach(function (item) {
                    var active = item === button;
                    item.classList.toggle("is-active", active);
                    item.setAttribute("aria-pressed", active ? "true" : "false");
                });
            });
        });

        filterbar.querySelectorAll("[data-profile-view]").forEach(function (button) {
            button.addEventListener("click", function () {
                var view = button.dataset.profileView || "grid";

                filterbar.querySelectorAll("[data-profile-view]").forEach(function (item) {
                    var active = item === button;
                    item.classList.toggle("is-active", active);
                    item.setAttribute("aria-pressed", active ? "true" : "false");
                });

                if (recipeGrid) {
                    recipeGrid.classList.toggle("is-list-view", view === "list");
                }
            });
        });
    });

    document.querySelectorAll(".home-search-form").forEach(function (form) {
        form.addEventListener("submit", function (event) {
            event.preventDefault();

            var action = form.getAttribute("action") || window.location.href;
            var formData = new FormData(form);
            var query = String(formData.get("q") || "").trim();

            if (query === "") {
                window.location.href = new URL(form.dataset.emptyAction || form.dataset.homeAction || "../home/", form.baseURI).toString();
                return;
            }

            var target = new URL(action, form.baseURI);

            target.search = "";
            ["q", "category", "difficulty", "sort"].forEach(function (key) {
                var value = formData.has(key) ? String(formData.get(key) || "") : "";

                if (key === "sort" && value === "") {
                    value = "newest";
                }

                target.searchParams.set(key, value);
            });

            window.location.href = target.toString();
        });
    });

    async function postSocialAction(endpoint, body) {
        var normalizedBase = apiBase.endsWith("/") ? apiBase : apiBase + "/";
        var response = await fetch(new URL(normalizedBase + endpoint, document.baseURI).toString(), {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                "X-CSRF-Token": csrfToken,
            },
            body: new URLSearchParams(body).toString(),
        });

        var payload = await response.json().catch(function () {
            return null;
        });

        if (!response.ok || !payload) {
            throw new Error((payload && payload.message) || "Gagal memproses aksi.");
        }

        return payload;
    }

    async function postReportAction(body) {
        var normalizedBase = apiBase.endsWith("/") ? apiBase : apiBase + "/";
        var response = await fetch(new URL(normalizedBase + "report.php", document.baseURI).toString(), {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                "X-CSRF-Token": csrfToken,
            },
            body: new URLSearchParams(body).toString(),
        });

        var payload = await response.json().catch(function () {
            return null;
        });

        if (!response.ok || !payload) {
            throw new Error((payload && payload.message) || "Gagal memproses laporan.");
        }

        return payload;
    }

    var followButtons = document.querySelectorAll('[data-social-action="follow"]');
    var followerCount = document.querySelector("[data-follower-count]");

    function updateFollowState(button, state) {
        if (!button) {
            return;
        }

        var following = !!state.following;
        var followLabel = button.dataset.followLabel || "Follow";
        var followedLabel = button.dataset.followedLabel || "Followed";
        var labelNode = button.querySelector("[data-follow-text]") || button.querySelector(".profile-account-item__follow-label");
        button.classList.toggle("is-active", following);
        if (labelNode) {
            labelNode.textContent = following ? followedLabel : followLabel;
        } else {
            button.textContent = following ? followedLabel : followLabel;
        }

        if (followerCount && typeof state.follower_count !== "undefined") {
            followerCount.textContent = String(state.follower_count);
        }
    }

    followButtons.forEach(function (followButton) {
        followButton.addEventListener("click", async function () {
            if (guestMode) {
                window.location.href = new URL(loginUrl, document.baseURI).toString();
                return;
            }

            try {
                followButton.disabled = true;
                followButton.classList.add("profile-actions__primary--disabled");
                var payload = await postSocialAction("follow.php", { user_id: followButton.dataset.userId || "" });
                updateFollowState(followButton, payload.data || {});
            } catch (error) {
                alert(error.message);
            } finally {
                followButton.disabled = false;
                followButton.classList.remove("profile-actions__primary--disabled");
            }
        });
    });

    function updateBookmarkIcon(button, favorited) {
        var icon = button ? button.querySelector("img") : null;

        if (!icon) {
            return;
        }

        if (!icon.dataset.inactiveSrc) {
            icon.dataset.inactiveSrc = (icon.getAttribute("src") || "").replace("icon-bookmark-active.svg", "icon-bookmark.svg");
        }

        if (!icon.dataset.activeSrc) {
            icon.dataset.activeSrc = icon.dataset.inactiveSrc.replace("icon-bookmark.svg", "icon-bookmark-active.svg");
        }

        icon.setAttribute("src", favorited ? icon.dataset.activeSrc : icon.dataset.inactiveSrc);
    }

    function updateCardFavoriteButtons(recipeId, favorited) {
        document.querySelectorAll('[data-card-favorite][data-recipe-id="' + recipeId + '"]').forEach(function (button) {
            button.classList.toggle("is-active", favorited);
            button.setAttribute("aria-pressed", favorited ? "true" : "false");
            updateBookmarkIcon(button, favorited);
        });
    }

    document.querySelectorAll("[data-card-favorite]").forEach(function (favoriteButton) {
        updateBookmarkIcon(favoriteButton, favoriteButton.classList.contains("is-active"));

        favoriteButton.addEventListener("click", async function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (guestMode) {
                window.location.href = new URL(loginUrl, document.baseURI).toString();
                return;
            }

            var recipeId = favoriteButton.dataset.recipeId || "";

            if (recipeId === "") {
                return;
            }

            try {
                favoriteButton.disabled = true;
                var payload = await postSocialAction("favorite.php", { recipe_id: recipeId });
                var state = payload.data || {};

                if (typeof state.favorited !== "undefined") {
                    updateCardFavoriteButtons(recipeId, !!state.favorited);
                }
            } catch (error) {
                alert(error.message);
            } finally {
                favoriteButton.disabled = false;
            }
        });
    });

    if (reportForm) {
        reportForm.addEventListener("submit", async function (event) {
            event.preventDefault();

            var formData = new FormData(reportForm);
            var category = String(formData.get("category") || "").trim();
            var note = String(formData.get("note") || "").trim();
            var targetType = String(formData.get("target_type") || "").trim();
            var targetId = String(formData.get("target_id") || "").trim();

            if (category === "" || note === "" || targetType === "" || targetId === "") {
                alert("Lengkapi kategori dan detail pengaduan.");
                return;
            }

            var submitButton = reportForm.querySelector("button[type='submit']");

            try {
                if (submitButton) {
                    submitButton.disabled = true;
                }

                var payload = await postReportAction({
                    target_type: targetType,
                    target_id: targetId,
                    category: category,
                    note: note,
                });

                reportForm.reset();
                if (document.body.classList.contains("cs-page") && showReportSuccessFeedback()) {
                    if (submitButton) {
                        submitButton.disabled = true;
                    }

                    reportSuccessTimer = window.setTimeout(function () {
                        window.location.href = new URL(reportSuccessRedirect, document.baseURI).toString();
                    }, reportSuccessDuration);
                } else {
                    closeReportModal();
                    alert(payload.message || "Pengaduan berhasil dikirim.");
                }
            } catch (error) {
                alert(error.message);
            } finally {
                if (submitButton && !reportSuccessTimer) {
                    submitButton.disabled = false;
                }
            }
        });
    }

    if (!detailPage) {
        return;
    }

    var detailRecipeId = document.body.dataset.recipeId || "";
    var likeButton = document.querySelector('[data-social-action="like"]');
    var favoriteButton = document.querySelector('[data-social-action="favorite"]');
    var rateButton = document.querySelector('[data-social-action="rate"]');
    var shareButton = document.querySelector('[data-social-action="share"]');
    var printButton = document.querySelector('[data-social-action="print"]');
    var commentForm = document.querySelector("[data-comment-form]");
    var commentList = document.querySelector("[data-comment-list]");
    var commentCount = document.querySelector("[data-comment-count]");
    var commentEmpty = document.querySelector("[data-comment-empty]");
    var commentTextarea = commentForm ? commentForm.querySelector('textarea[name="content"]') : null;
    var likeCount = document.querySelector("[data-like-count]");
    var favoriteCount = document.querySelector("[data-favorite-count]");
    var ratingAverage = document.querySelector("[data-rating-average]");
    var userRating = document.querySelector("[data-user-rating]");

    function recipeId() {
        if (likeButton && likeButton.dataset.recipeId) {
            return likeButton.dataset.recipeId;
        }

        if (commentForm) {
            var recipeField = commentForm.querySelector('input[name="recipe_id"]');
            if (recipeField && recipeField.value) {
                return recipeField.value;
            }
        }

        return detailRecipeId;
    }

    function updateSocialState(state) {
        if (likeButton) {
            likeButton.classList.toggle("is-active", !!state.liked);
        }
        if (favoriteButton) {
            favoriteButton.classList.toggle("is-active", !!state.favorited);
        }
        if (likeCount && typeof state.likes_count !== "undefined") {
            likeCount.textContent = String(state.likes_count);
        }
        if (favoriteCount && typeof state.favorites_count !== "undefined") {
            favoriteCount.textContent = String(state.favorites_count);
        }
        if (ratingAverage && typeof state.rating_average !== "undefined") {
            ratingAverage.textContent = Number(state.rating_average).toFixed(1) + " ★";
        }
        if (userRating && typeof state.user_rating !== "undefined" && state.user_rating !== null) {
            userRating.textContent = Number(state.user_rating).toFixed(1);
        }
    }

    function escapeHtml(value) {
        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    function renderComment(comment) {
        if (!comment) {
            return "";
        }

        var avatar = comment.avatar || "../assets/img/home-profile.png";
        var author = comment.author || "User";
        var authorRole = comment.author_role || "pengguna";
        var createdAt = comment.created_at_label || comment.created_at || "";
        var content = comment.content || "";

        return '' +
            '<article class="detail-comment">' +
                '<img class="detail-comment__avatar" src="' + escapeHtml(avatar) + '" alt="' + escapeHtml(author) + '">' +
                '<div class="detail-comment__body">' +
                    '<div class="detail-comment__bubble">' +
                        '<div class="detail-comment__meta">' +
                            '<span class="detail-comment__author">' +
                                '<strong>' + escapeHtml(author) + '</strong>' +
                                renderAdminBadge(authorRole) +
                            '</span>' +
                            '<span class="detail-comment__time">' + escapeHtml(createdAt) + '</span>' +
                        '</div>' +
                        '<p>' + escapeHtml(content) + '</p>' +
                    '</div>' +
                    '<div class="detail-comment__actions" aria-label="Aksi komentar">' +
                        '<button type="button" class="detail-comment__action" disabled>Suka</button>' +
                        '<button type="button" class="detail-comment__action" disabled>Balas</button>' +
                    '</div>' +
                '</div>' +
            '</article>';
    }

    function renderAdminBadge(role) {
        if (role !== "admin") {
            return "";
        }

        return '' +
            '<span class="detail-admin-badge" aria-label="Admin">' +
                '<span class="detail-admin-badge__icon" aria-hidden="true">' +
                    '<svg viewBox="0 0 24 24" focusable="false">' +
                        '<circle cx="12" cy="12" r="10" fill="currentColor"></circle>' +
                        '<path d="m8.4 12.3 2.3 2.3 4.9-5" fill="none" stroke="#ffffff" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"></path>' +
                    '</svg>' +
                '</span>' +
                '<span class="detail-admin-badge__label">admin</span>' +
            '</span>';
    }

    function resizeCommentTextarea() {
        if (!commentTextarea) {
            return;
        }

        commentTextarea.style.height = "auto";
        commentTextarea.style.height = Math.min(commentTextarea.scrollHeight, 72) + "px";
    }

    function updateCommentState(comments, count) {
        if (!commentList) {
            return;
        }

        var list = Array.isArray(comments) ? comments : [];

        if (commentCount && typeof count !== "undefined") {
            commentCount.textContent = String(count);
        }

        if (list.length === 0) {
            commentList.innerHTML = '' +
                '<div class="detail-comments__empty" data-comment-empty>' +
                    '<h3>Belum ada komentar</h3>' +
                    '<p>Jadilah yang pertama memberi komentar pada resep ini.</p>' +
                '</div>';
            commentEmpty = document.querySelector("[data-comment-empty]");
            return;
        }

        commentList.innerHTML = list.map(renderComment).join("");
        commentEmpty = null;
    }

    if (commentTextarea) {
        resizeCommentTextarea();
        commentTextarea.addEventListener("input", resizeCommentTextarea);
    }

    if (likeButton) {
        likeButton.addEventListener("click", async function () {
            if (guestMode) {
                return;
            }
            try {
                likeButton.disabled = true;
                var payload = await postSocialAction("like.php", { recipe_id: recipeId() });
                updateSocialState(payload.data || {});
            } catch (error) {
                alert(error.message);
            } finally {
                likeButton.disabled = false;
            }
        });
    }

    if (favoriteButton) {
        favoriteButton.addEventListener("click", async function () {
            if (guestMode) {
                return;
            }
            try {
                favoriteButton.disabled = true;
                var payload = await postSocialAction("favorite.php", { recipe_id: recipeId() });
                updateSocialState(payload.data || {});
            } catch (error) {
                alert(error.message);
            } finally {
                favoriteButton.disabled = false;
            }
        });
    }

    if (rateButton) {
        rateButton.addEventListener("click", async function () {
            if (guestMode) {
                return;
            }
            var current = userRating && userRating.textContent !== "0.0" ? userRating.textContent : "5";
            var input = window.prompt("Masukkan rating 1-5", current);
            if (input === null) {
                return;
            }

            try {
                rateButton.disabled = true;
                var payload = await postSocialAction("rating.php", {
                    recipe_id: recipeId(),
                    rating_value: input,
                });
                updateSocialState(payload.data || {});
            } catch (error) {
                alert(error.message);
            } finally {
                rateButton.disabled = false;
            }
        });
    }

    if (shareButton) {
        shareButton.addEventListener("click", async function () {
            var url = shareButton.dataset.shareUrl || window.location.href;
            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(url);
                    alert("Link resep disalin.");
                } else {
                    window.prompt("Salin link resep ini:", url);
                }
            } catch (error) {
                window.prompt("Salin link resep ini:", url);
            }
        });
    }

    if (printButton) {
        printButton.addEventListener("click", function () {
            window.print();
        });
    }

    if (commentForm) {
        commentForm.addEventListener("submit", async function (event) {
            event.preventDefault();

            if (!commentTextarea) {
                return;
            }

            var content = commentTextarea.value.trim();
            if (content === "") {
                alert("Komentar tidak boleh kosong.");
                commentTextarea.focus();
                return;
            }

            try {
                commentForm.querySelector("button[type='submit']").disabled = true;
                var response = await fetch("../api/komentar.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                        "X-CSRF-Token": csrfToken,
                    },
                    body: new URLSearchParams({
                        recipe_id: recipeId(),
                        content: content,
                    }).toString(),
                });

                var payload = await response.json().catch(function () {
                    return null;
                });

                if (!response.ok || !payload) {
                    throw new Error((payload && payload.message) || "Gagal memproses komentar.");
                }

                updateCommentState(payload.data && payload.data.comments ? payload.data.comments : [], payload.data && payload.data.comments_count);
                commentTextarea.value = "";
                resizeCommentTextarea();
            } catch (error) {
                alert(error.message);
            } finally {
                commentForm.querySelector("button[type='submit']").disabled = false;
            }
        });
    }

});
