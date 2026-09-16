import sys

content = open('user_app/lib/screens/profile_screen.dart', 'r', encoding='utf-8').read()

old_logic = """                      Color statusBg = AppColors.statusPendingBg;
                      Color statusText = AppColors.statusPendingText;
                      if (rep.status == 'dalam pengerjaan') {
                        statusBg = AppColors.statusProcessBg;
                        statusText = AppColors.statusProcessText;
                      } else if (rep.status == 'selesai') {
                        statusBg = AppColors.statusSuccessBg;
                        statusText = AppColors.statusSuccessText;
                      }

                      return Card(
                        margin: const EdgeInsets.only(bottom: 12),
                        elevation: 0,
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                            side: const BorderSide(color: AppColors.border)),
                        child: InkWell(
                          onTap: () => _showReportDetail(context, rep),
                          borderRadius: BorderRadius.circular(16),
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                      decoration: BoxDecoration(
                                        color: statusBg,
                                        borderRadius: BorderRadius.circular(10),
                                      ),
                                      child: Text(
                                        rep.status.toUpperCase(),"""

new_logic = """                      Color statusBg = AppColors.statusPendingBg;
                      Color statusText = AppColors.statusPendingText;
                      String displayStatus = rep.status.toUpperCase();
                      
                      if (rep.status == 'diproses') {
                        if (rep.penugasanStatus == 'ditugaskan') {
                          displayStatus = 'DIVERIFIKASI (DISPOSISI)';
                        } else if (rep.penugasanStatus == 'diterima') {
                          displayStatus = 'DALAM PENGERJAAN';
                        } else {
                          displayStatus = 'DIPROSES';
                        }
                        statusBg = AppColors.statusProcessBg;
                        statusText = AppColors.statusProcessText;
                      } else if (rep.status == 'selesai') {
                        displayStatus = 'SELESAI';
                        statusBg = AppColors.statusSuccessBg;
                        statusText = AppColors.statusSuccessText;
                      }

                      return Card(
                        margin: const EdgeInsets.only(bottom: 12),
                        elevation: 0,
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                            side: const BorderSide(color: AppColors.border)),
                        child: InkWell(
                          onTap: () => _showReportDetail(context, rep),
                          borderRadius: BorderRadius.circular(16),
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                      decoration: BoxDecoration(
                                        color: statusBg,
                                        borderRadius: BorderRadius.circular(10),
                                      ),
                                      child: Text(
                                        displayStatus,"""

content = content.replace(old_logic, new_logic, 1)

with open('user_app/lib/screens/profile_screen.dart', 'w', encoding='utf-8') as f:
    f.write(content)
