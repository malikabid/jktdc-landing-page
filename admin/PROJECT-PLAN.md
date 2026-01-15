# DOTK Admin CMS - Project Plan

## 📋 Project Overview

**Goal**: Build a lightweight, modular CMS using Slim Framework to manage dynamic content for the DOTK static website.

**Approach**: 
- Phase 1: Core foundation with API
- Phase 2: Admin UI with Twig
- Phase 3: Advanced features & optimization

**Tech Stack**:
- **Framework**: Slim 4
- **Authentication**: JWT
- **Templating**: Twig (Phase 2+)
- **Database**: SQLite/MySQL (user management)
- **Data Storage**: JSON files (notifications, events, tenders)
- **RBAC**: Custom ACL system

---

## 🎯 Phase 1: Foundation & API Layer
**Timeline**: 2-3 weeks | **Priority**: HIGH

### Week 1: Setup & Core Infrastructure (8-10 hours)

#### Tasks:
- [ ] **Project Setup** (2 hours)
  - Initialize Slim 4 project
  - Setup composer dependencies
  - Configure directory structure
  - Setup .env configuration
  - Configure error handling & logging

- [ ] **Database Setup** (2 hours)
  - Create database schema (users, roles, permissions, sessions)
  - Setup Eloquent ORM (standalone)
  - Create migration system
  - Seed initial admin user

- [ ] **Authentication System** (4-6 hours)
  - Implement JWT authentication
  - Create login/logout endpoints
  - Password hashing (bcrypt)
  - Token refresh mechanism
  - Auth middleware

**Deliverables**:
- ✅ Working Slim 4 installation
- ✅ Database with user tables
- ✅ POST /api/auth/login
- ✅ POST /api/auth/logout
- ✅ POST /api/auth/refresh
- ✅ Auth middleware protecting routes

---

### Week 2: RBAC & Module Foundation (10-12 hours)

#### Tasks:
- [ ] **RBAC System** (4-5 hours)
  - Define roles (admin, editor, viewer)
  - Create permissions structure
  - Build RBAC middleware
  - Permission checking helpers
  - Seed default roles & permissions

- [ ] **Module Architecture** (3-4 hours)
  - Create base module structure
  - Module auto-loading system
  - Permission registration per module
  - Route registration per module

- [ ] **Notifications Module (MVP)** (3-4 hours)
  - GET /api/notifications (list)
  - GET /api/notifications/:id (single)
  - POST /api/notifications (create)
  - PUT /api/notifications/:id (update)
  - DELETE /api/notifications/:id (delete)
  - Read/Write to JSON file
  - Validate notification data

**Deliverables**:
- ✅ Working RBAC system
- ✅ Module architecture pattern
- ✅ Complete Notifications CRUD API
- ✅ Permission-based access control
- ✅ JSON file management

---

### Week 3: Additional Modules (12-15 hours)

#### Tasks:
- [ ] **Events Module** (4-5 hours)
  - Full CRUD API endpoints
  - Event date validation
  - JSON file management
  - Pagination support

- [ ] **Tenders Module** (4-5 hours)
  - Full CRUD API endpoints
  - File upload support (PDF)
  - Deadline validation
  - JSON file management

- [ ] **Officials Module** (3-4 hours)
  - Full CRUD API endpoints
  - Image upload support
  - Order/priority management
  - JSON file management

- [ ] **Testing & Documentation** (2-3 hours)
  - API endpoint testing
  - Postman collection
  - API documentation
  - Error handling validation

**Deliverables**:
- ✅ Events API (fully functional)
- ✅ Tenders API (fully functional)
- ✅ Officials API (fully functional)
- ✅ API documentation (Postman/OpenAPI)
- ✅ Unit tests for critical paths

---

## 🎨 Phase 2: Admin UI with Twig
**Timeline**: 2-3 weeks | **Priority**: MEDIUM

### Week 4: UI Foundation & Authentication (8-10 hours)

#### Tasks:
- [ ] **Twig Integration** (2 hours)
  - Install slim/twig-view
  - Setup template directory structure
  - Create base layout template
  - Add asset pipeline (CSS/JS)

- [ ] **Authentication UI** (3-4 hours)
  - Login page design
  - Session management
  - Remember me functionality
  - Password reset flow (optional)

- [ ] **Dashboard** (3-4 hours)
  - Main dashboard layout
  - Navigation menu
  - Statistics cards
  - Recent activity feed

**Deliverables**:
- ✅ Login page (functional)
- ✅ Admin dashboard
- ✅ Base layout template
- ✅ Navigation system

---

### Week 5: Module UI - Part 1 (13-15 hours)

#### Tasks:
- [ ] **Notifications UI** (5-6 hours)
  - List view with filters
  - Create/edit form
  - Delete confirmation
  - Priority badges
  - Search functionality

- [ ] **Events UI** (5-6 hours)
  - Calendar view
  - List view with filters
  - Create/edit form
  - Date picker integration
  - Bulk actions

- [ ] **Two-Factor Authentication (2FA)** (3 hours)
  - Install robthree/twofactorauth package
  - Add database fields (secret, enabled, recovery codes)
  - Enable 2FA endpoint with QR code generation
  - Verify & activate 2FA endpoint
  - Update login flow to check for 2FA
  - Generate recovery codes
  - 2FA settings page UI
  - 2FA verification page at login
  - Rate limiting for 2FA attempts

**Deliverables**:
- ✅ Notifications management interface
- ✅ Events management interface
- ✅ Form validation (client & server)
- ✅ Two-Factor Authentication (TOTP)

---

### Week 6: Module UI - Part 2 (10-12 hours)

#### Tasks:
- [ ] **Tenders UI** (5-6 hours)
  - List view with status filters
  - Create/edit form
  - File upload interface
  - Deadline alerts
  - Archive functionality

- [ ] **Officials UI** (3-4 hours)
  - List view with drag-and-drop reorder
  - Create/edit form
  - Image upload with preview
  - Quick edit modal

- [ ] **User Management UI** (2-3 hours)
  - List users
  - Create/edit users
  - Assign roles
  - Activity log

**Deliverables**:
- ✅ Tenders management interface
- ✅ Officials management interface
- ✅ User management interface
- ✅ Responsive design (mobile-friendly)

---

## 🚀 Phase 3: Advanced Features & Polish
**Timeline**: 1-2 weeks | **Priority**: LOW-MEDIUM

### Week 7: Enhancement & Optimization (8-12 hours)

#### Tasks:
- [ ] **File Management** (3-4 hours)
  - Media library
  - Image optimization
  - File browser
  - Storage quota management

- [ ] **Activity Logging** (2-3 hours)
  - Log all CRUD operations
  - User action tracking
  - Audit trail view

- [ ] **Backup & Export** (2-3 hours)
  - JSON backup system
  - Database backup
  - Export to CSV/Excel
  - Automated backups

- [ ] **Performance** (2-3 hours)
  - Caching layer (Redis/File)
  - Query optimization
  - Asset minification
  - Lazy loading

**Deliverables**:
- ✅ Media management system
- ✅ Activity logs
- ✅ Backup system
- ✅ Performance optimizations

---

## 📦 Optional Features (Future Phases)

### Advanced Features (2-4 weeks)
- [ ] **Content Versioning** (1 week)
  - Track changes history
  - Restore previous versions
  - Compare versions

- [ ] **Workflow & Approval** (1 week)
  - Draft/Published states
  - Approval workflow
  - Email notifications

- [ ] **SMS-Based 2FA** (2-3 days)
  - Alternative to authenticator app
  - Integrate Twilio/AWS SNS
  - Fallback option for users

- [ ] **Multi-language Support** (1 week)
  - i18n for admin UI
  - Content translation
  - Language switcher

- [ ] **Advanced RBAC** (3-5 days)
  - Custom role creation
  - Granular permissions
  - Department-based access

- [ ] **GraphQL API** (3-5 days)
  - GraphQL endpoint
  - Schema generation
  - Query optimization

- [ ] **Real-time Features** (1 week)
  - WebSocket support
  - Live notifications
  - Collaborative editing

---

## 📊 Timeline Summary

| Phase | Duration | Effort | Priority |
|-------|----------|--------|----------|
| **Phase 1: API Layer** | 2-3 weeks | 30-37 hours | 🔴 HIGH |
| **Phase 2: Admin UI** | 2-3 weeks | 31-37 hours | 🟡 MEDIUM |
| **Phase 3: Advanced** | 1-2 weeks | 8-12 hours | 🟢 LOW |
| **Total (MVP)** | **5-8 weeks** | **69-86 hours** | |

---

## 🎯 Milestones

### Milestone 1: API MVP ✅ (End of Week 3)
- Authentication working
- All 4 modules with CRUD APIs
- RBAC functional
- API documentation complete

### Milestone 2: Admin UI MVP ✅ (End of Week 6)
- Login interface
- All modules have UI
- User management
- Responsive design

### Milestone 3: Production Ready ✅ (End of Week 7)
- Performance optimized
- Backup system
- Activity logging
- Deployment ready

---

## 🛠️ Development Approach

### Daily Workflow:
1. **Morning**: Plan tasks for the day
2. **Development**: Focus on one module at a time
3. **Testing**: Test after each feature
4. **Commit**: Small, frequent commits
5. **Review**: End-of-day progress review

### Weekly Rhythm:
- **Monday**: Plan week's tasks
- **Tuesday-Thursday**: Core development
- **Friday**: Testing, documentation, review
- **Weekend**: Optional catch-up

### Best Practices:
- ✅ Write tests for critical paths
- ✅ Document as you build
- ✅ Security-first approach
- ✅ Mobile-responsive from day 1
- ✅ Git branches per feature
- ✅ Code review before merge

---

## 📦 Dependencies & Requirements

### Server Requirements:
- PHP 8.0+
- Composer
- Apache/Nginx with mod_rewrite
- SQLite or MySQL
- 256MB+ memory

### Development Tools:
- PHP IDE (VS Code with extensions)
- Postman (API testing)
- Git
- Node.js & npm (for asset compilation)

### Core Libraries:
- robthree/twofactorauth (2FA/TOTP)
- tuupola/slim-jwt-auth (JWT authentication)
- slim/twig-view (templating)
- illuminate/database (Eloquent ORM)

### Third-party Services (Optional):
- Email service (SendGrid/Mailgun) for notifications
- SMS service (Twilio) for 2FA via SMS (optional)
- Image optimization service
- Backup storage (S3/Wasabi)

---

## 🚨 Risks & Mitigation

| Risk | Impact | Mitigation |
|------|--------|------------|
| Scope creep | High | Strict phase boundaries, MVP focus |
| GoDaddy limitations | Medium | Test early, use fallbacks |
| Security vulnerabilities | High | Security audit, input validation |
| Performance issues | Medium | Caching, profiling, optimization |
| Learning curve (Slim) | Low | Good documentation, simple framework |

---

## 🎓 Learning Resources

### Slim Framework:
- Official Docs: https://www.slimframework.com/docs/v4/
- Tutorial: https://www.slimframework.com/docs/v4/start/installation.html

### Twig:
- Official Docs: https://twig.symfony.com/doc/3.x/
- Tutorial: https://twig.symfony.com/doc/3.x/templates.html

### JWT Authentication:
- JWT.io: https://jwt.io/
- tuupola/slim-jwt-auth: https://github.com/tuupola/slim-jwt-auth

---

## 📝 Next Steps

### Immediate Actions:
1. ✅ Review and approve this plan
2. ⬜ Setup development environment
3. ⬜ Initialize Slim project
4. ⬜ Create project repository structure
5. ⬜ Start Phase 1, Week 1 tasks

### Questions to Clarify:
- [ ] Preferred database: SQLite or MySQL?
- [ ] Email service for password reset?
- [ ] Backup frequency and retention?
- [ ] Multi-user support from day 1 or later?
- [ ] Development vs Production environments?

---

## 💰 Effort Breakdown

### By Phase:
- **Phase 1 (API)**: 30-37 hours → ~40% of total effort
- **Phase 2 (UI + 2FA)**: 31-37 hours → ~40% of total effort  
- **Phase 3 (Polish)**: 8-12 hours → ~10% of total effort
- **Testing & Docs**: Throughout → ~10% of total effort

### By Module:
- **Core (Auth, RBAC)**: ~18 hours
- **Two-Factor Auth (2FA)**: ~3 hours
- **Notifications**: ~8 hours
- **Events**: ~9 hours
- **Tenders**: ~9 hours
- **Officials**: ~7 hours
- **UI Foundation**: ~10 hours
- **Module UIs**: ~23 hours

---

## 📞 Support & Escalation

### Technical Blockers:
- Research & document issue
- Check Slim/Twig documentation
- Search GitHub issues
- Ask in Slim Framework discussions

### Decision Points:
- Document options with pros/cons
- Recommend best approach
- Get approval before proceeding

---

## ✅ Success Criteria

### Phase 1 Success:
- ✅ Can authenticate via API
- ✅ Can CRUD all content types via API
- ✅ Permissions properly restrict access
- ✅ JSON files update correctly
- ✅ API documentation complete

### Phase 2 Success:
- ✅ Non-technical users can manage content
- ✅ UI is intuitive and responsive
- ✅ Forms validate properly
- ✅ File uploads work reliably
- ✅ Two-Factor Authentication working
- ✅ No security vulnerabilities

### Phase 3 Success:
- ✅ Site loads quickly (<2s)
- ✅ Backups run automatically
- ✅ Activity is logged
- ✅ Ready for production deployment

---

**Last Updated**: January 15, 2026  
**Status**: Planning Phase  
**Next Review**: After Phase 1 completion
