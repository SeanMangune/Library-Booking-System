# Title Page (Student Paper)
**Designing and Securing an IT Infrastructure for a Medium-Sized Company**  
**Capstone Context: SmartSpace Library Booking System**  
Your Name  
School / University  
Course Name  
Instructor’s Name  
May 8, 2026  

# Abstract
This report presents a secure and manageable IT infrastructure design for a medium-sized organization of 50 employees across HR, Finance, IT, and Sales. The design is grounded in the organization’s capstone system, the SmartSpace Library Booking System, a Laravel (PHP 8.2) web application with a mobile-first experience used to reserve library rooms, manage schedules, and provide staff reporting. The proposed infrastructure emphasizes availability, confidentiality, and operational efficiency through clear administrative roles, standardized maintenance schedules, layered security controls, and robust storage and backup practices. Key components include Windows Server-based directory services, role-based access control, NTFS permissions, centralized monitoring, an on-premises NAS for file sharing and backup, and a secured IIS web tier for hosting internal services. The report also outlines business continuity measures, disaster recovery procedures, and policy frameworks that align with least-privilege principles and compliance expectations. The result is a cohesive, scalable, and secure infrastructure blueprint that supports both day-to-day operations and the continued availability of the capstone library booking platform.

# Main Body

## 1. The Gatekeepers of IT: System Administration
System administrators are responsible for keeping core services secure and operational. For this organization, responsibilities are split to enforce separation of duties:
- **IT Head**: Approves budgets, security direction, and risk acceptance.
- **Project Manager**: Coordinates upgrades and change management.
- **Security Administrator**: Manages identity, access control, antivirus/EDR, and security monitoring.
- **Network Administrator**: Maintains routing, switching, VLANs, firewall rules, and VPN.
- **Systems Administrator**: Manages servers, backups, patching, and virtualization.
- **Application Administrator**: Maintains the SmartSpace Library Booking System, database, and integrations.

## 2. Operating System and Maintenance
**OS Selection**: Windows Server (for AD, file/print services) and Ubuntu Server (optional for application hosting).  
**Updates and Patching**:
- **Daily**: Verify endpoint protection status, review alerts, check critical service health.
- **Weekly**: Apply non-disruptive patches in a maintenance window; verify backups.
- **Monthly**: Apply OS and application patches to servers, rotate credentials, review logs.
**Antivirus and Monitoring**: Endpoint protection on all workstations/servers; central log collection and alerting for authentication, file access, and SmartSpace service status.

## 3. Devices, Drivers, Management Console & Tools
**Devices**:
- 2–3 physical servers (AD/file/print, application/database, backup)
- 50 workstations/laptops
- 2–3 network printers
- Network switches, firewall, wireless access points
**Driver Management**: Standardized driver packages, vendor-approved updates, and staged rollouts.  
**Tools**:
- **Device Manager**: Verify device health and drivers.
- **Task Manager**: Monitor CPU/memory/network usage.
- **Event Viewer**: Track OS, security, and application logs.

## 4. Managing Storage
**Storage Plan**:
- **Local storage**: OS and application binaries on servers and workstations.
- **Centralized storage**: Department shares on a file server for HR/Finance.
**Disk Quotas**: Enforced on shared storage to prevent abuse and protect capacity.  
**Backup Strategy**:
- Daily incremental backups
- Weekly full backups
- Monthly archival backups retained offsite

## 5. NAS and Storage Network
**NAS Proposal**: A dedicated NAS for file sharing, backup targets, and versioned snapshots.  
**Benefits**:
- Centralized access for HR/Finance files
- Snapshot-based recovery
- RAID for redundancy
- Faster restore for SmartSpace backups

## 6. Disk Structure
**Partitions**:
- **Primary**: OS and boot partitions
- **Extended/Logical**: Application data and logs where appropriate
**File Systems**:
- **NTFS** for Windows servers/workstations (permissions, quotas, auditing)
- **EXT4** for Linux application servers
**Server Layout Example**:
- C:\ (OS, 150 GB)
- D:\ (Applications, 200 GB)
- E:\ (Data/Backups, 1–2 TB)

## 7. Managing Information Technology
**Policies**:
- **Acceptable Use Policy**: Device usage, internet access, and software rules.
- **Security Policy**: Passwords, MFA, data classification, incident reporting.
**IT Management Structure**: Clear escalation paths, change management approval, and a ticketing system for support requests.

## 8. Business Continuity
**Disaster Recovery Plan**:
- **Backup Schedule**: Daily incremental, weekly full, monthly archival.
- **Recovery Procedures**: Restore priority—AD, file server, SmartSpace application, database.
- **Risk Analysis**: Power loss, ransomware, hardware failure, and data corruption; mitigation via UPS, backups, and network segmentation.

## 9. HOSTS and LMHOSTS
**Hostname Resolution**:
- DNS handles primary name resolution.
- HOSTS/LMHOSTS entries override DNS locally for troubleshooting or isolated systems.
**Sample HOSTS Entries**:
```
10.10.10.10  smartspace.local
10.10.10.20  fileserver.local
10.10.10.30  printserver.local
```

## 10. Directory Services with Active Directory
**Domain Structure**: `smartspace.local`  
**Organizational Units (OUs)**:
- HR, Finance, IT, Sales, Library Staff, Students
**Users and Groups**:
- HR_Users, Finance_Users, IT_Admins, Librarians, SmartSpace_AppAdmins
This enables centralized authentication and policy enforcement.

## 11. Group Policy
**Sample Policies**:
1. **Password Policy**: 12+ characters, complexity, 90-day rotation, MFA for admins.
2. **Desktop Restrictions**: Lock screen after 10 minutes, disable USB storage for non-IT.
3. **Software Installation Rules**: Allow only approved software via managed deployment.

## 12. Effective NTFS Permissions
**HR**: Confidential access to HR share; no access to Finance data.  
**Finance**: Restricted access to financial records with audit logging.  
**Least Privilege**: Users receive only the permissions required for their role.

## 13. Managing Print Jobs
**Network Printers**: Deployed via print server with per-department access.  
**Print Queue Management**: Admins can pause, re-prioritize, and clear queues.  
**User Access Control**: HR and Finance printers restricted to their groups.

## 14. Managing Websites with IIS
**Web Hosting**: IIS hosts internal portals and documentation; SmartSpace can be reverse-proxied if needed.  
**Security Settings**:
- HTTPS with TLS certificates
- Windows Authentication for internal portals
- Application pool isolation and least-privilege service accounts

## Capstone Alignment: SmartSpace Library Booking System
The SmartSpace Library Booking System (Laravel 12, PHP 8.2, Vite/Tailwind) requires reliable authentication, a secure database, and staff reporting. The infrastructure above provides:
- Segmented network access for staff and students
- Centralized identity via AD groups aligned to SmartSpace roles (admin, librarian, user)
- Backups and monitoring to protect booking data and reports
- Secure IIS hosting for internal services and documentation

## Conclusion
This infrastructure design balances operational efficiency with security. It provides structured administration, predictable maintenance, secure storage, and resilient recovery for a medium-sized organization. The blueprint directly supports the SmartSpace Library Booking System by ensuring consistent uptime, protected data, and manageable access controls across all departments.

# References
National Institute of Standards and Technology. (2010). *Contingency planning guide for federal information systems (SP 800-34 Rev. 1).*  
Microsoft. (2024). *Active Directory Domain Services overview.*  
Microsoft. (2024). *NTFS permissions and security.*  
