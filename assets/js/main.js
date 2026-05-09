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
    var detailPage = document.body.classList.contains("detail-page");
    var profileEditModal = document.querySelector("[data-profile-edit-modal]");
    var profileEditOpeners = document.querySelectorAll("[data-profile-edit-open]");
    var profileEditClosers = document.querySelectorAll("[data-profile-edit-close]");

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
        var response = await fetch("../api/" + endpoint, {
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

    var followButtons = document.querySelectorAll('[data-social-action="follow"]');
    var followerCount = document.querySelector("[data-follower-count]");

    function updateFollowState(button, state) {
        if (!button) {
            return;
        }

        var following = !!state.following;
        var followLabel = button.dataset.followLabel || "Follow";
        var followedLabel = button.dataset.followedLabel || "Followed";
        button.classList.toggle("is-active", following);
        button.textContent = following ? followedLabel : followLabel;

        if (followerCount && typeof state.follower_count !== "undefined") {
            followerCount.textContent = String(state.follower_count);
        }
    }

    followButtons.forEach(function (followButton) {
        followButton.addEventListener("click", async function () {
            if (guestMode) {
                window.location.href = "../auth/login.php";
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

    if (!detailPage) {
        return;
    }

    var likeButton = document.querySelector('[data-social-action="like"]');
    var favoriteButton = document.querySelector('[data-social-action="favorite"]');
    var rateButton = document.querySelector('[data-social-action="rate"]');
    var shareButton = document.querySelector('[data-social-action="share"]');
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
        return likeButton ? likeButton.dataset.recipeId : "";
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
        var createdAt = comment.created_at_label || comment.created_at || "";
        var content = comment.content || "";

        return '' +
            '<article class="detail-comment">' +
                '<img class="detail-comment__avatar" src="' + escapeHtml(avatar) + '" alt="' + escapeHtml(author) + '">' +
                '<div class="detail-comment__body">' +
                    '<div class="detail-comment__meta">' +
                        '<strong>' + escapeHtml(author) + '</strong>' +
                        '<span>' + escapeHtml(createdAt) + '</span>' +
                    '</div>' +
                    '<p>' + escapeHtml(content) + '</p>' +
                '</div>' +
            '</article>';
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
            } catch (error) {
                alert(error.message);
            } finally {
                commentForm.querySelector("button[type='submit']").disabled = false;
            }
        });
    }

});
